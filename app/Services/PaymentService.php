<?php

namespace App\Services;

use App\Contracts\Payments\PaymentGateway;
use App\Models\BillingInvoice;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\PaymentAttempt;
use App\Models\PaymentWebhookEvent;
use App\Models\ServiceActionLog;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class PaymentService
{
    public function createAttempt(BillingInvoice $invoice, string $method): PaymentAttempt
    {
        if (! in_array($invoice->status, ['pending', 'overdue'], true)) {
            throw new \RuntimeException('Invoice tidak dapat menerima pembayaran baru.');
        }

        $result = $this->gateway()->createPaymentAttempt($invoice->load('member'), $method);
        $token = Str::random(64);

        return $invoice->paymentAttempts()->create([
            'gateway' => $result['gateway'] ?? 'doku',
            'channel' => $result['channel'] ?? $method,
            'provider_reference' => $result['provider_reference'] ?? null,
            'amount' => $invoice->amount,
            'status' => 'pending',
            'payment_url' => $result['payment_url'] ?? null,
            'qr_content' => $result['qr_content'] ?? null,
            'public_token_hash' => hash('sha256', $token),
            'public_token_encrypted' => encrypt($token),
            'public_token_expires_at' => now()->addDays(7),
            'expires_at' => isset($result['expires_at']) ? Carbon::parse($result['expires_at']) : now()->addDays(7),
            'metadata' => $result['metadata'] ?? null,
            'provisioning_status' => 'pending',
        ])->tap(function (PaymentAttempt $attempt) use ($token) {
            $attempt->public_token = $token;
        });
    }

    public function createReusableVa(Member $member, string $bank = ''): PaymentAccount
    {
        $bank = strtoupper(trim($bank));
        if ($bank !== '' && $member->paymentAccounts()->where('gateway', 'doku')->where('bank', $bank)->where('status', 'active')->exists()) {
            return $member->paymentAccounts()
                ->where('gateway', 'doku')
                ->where('bank', $bank)
                ->where('status', 'active')
                ->firstOrFail();
        }

        $result = $this->gateway()->createReusableVirtualAccount($member, $bank);

        return DB::transaction(function () use ($member, $result) {
            if ($result['is_default'] ?? true) {
                $member->paymentAccounts()->where('gateway', 'doku')->update(['is_default' => false]);
            }

            return $member->paymentAccounts()->create([
                'gateway' => $result['gateway'] ?? 'doku',
                'bank' => $result['bank'],
                'channel' => $result['channel'] ?? null,
                'provider_customer_id' => $result['provider_customer_id'] ?? null,
                'partner_service_id' => $result['partner_service_id'] ?? null,
                'doku_va_channel_id' => $result['doku_va_channel_id'] ?? null,
                'provider_account_id' => $result['provider_account_id'] ?? null,
                'account_number' => $result['account_number'],
                'status' => $result['status'] ?? 'active',
                'is_default' => $result['is_default'] ?? true,
                'metadata' => $result['metadata'] ?? null,
            ]);
        });
    }

    public function processWebhook(array $headers, string $rawBody, string $path): PaymentWebhookEvent
    {
        if (! $this->gateway()->verifyWebhook($headers, $rawBody, $path)) {
            throw new \RuntimeException('Signature DOKU tidak valid.');
        }

        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) {
            throw new \RuntimeException('Payload webhook DOKU tidak valid.');
        }

        $eventId = (string) ($headers['x-external-id'] ?? $headers['X-EXTERNAL-ID'] ?? $headers['x-request-id'] ?? $headers['X-REQUEST-ID'] ?? '');
        if ($eventId === '') {
            $eventId = hash('sha256', $rawBody . '|' . ($headers['x-timestamp'] ?? $headers['X-TIMESTAMP'] ?? ''));
        }

        try {
            $event = PaymentWebhookEvent::create([
                'gateway' => 'doku',
                'provider_event_id' => $eventId,
                'event_type' => data_get($payload, 'service.id') ?? data_get($payload, 'additionalInfo.origin.product'),
                'status' => 'received',
                'signature_hash' => hash('sha256', (string) ($headers['x-signature'] ?? $headers['X-SIGNATURE'] ?? '')),
                'headers' => $this->safeHeaders($headers),
                'payload' => $payload,
            ]);
        } catch (UniqueConstraintViolationException) {
            $event = PaymentWebhookEvent::where('gateway', 'doku')->where('provider_event_id', $eventId)->firstOrFail();
            if ($event->status === 'processed') {
                return $event;
            }
            $event->update([
                'status' => 'received',
                'headers' => $this->safeHeaders($headers),
                'payload' => $payload,
                'processing_error' => null,
            ]);
        }

        try {
            $this->applyWebhook($event, $payload);
            $event->update(['status' => 'processed', 'processed_at' => now(), 'processing_error' => null]);
        } catch (Throwable $e) {
            $event->update(['status' => 'failed', 'processing_error' => $e->getMessage()]);
            throw $e;
        }

        return $event->fresh();
    }

    private function gateway(): PaymentGateway
    {
        return app(PaymentGateway::class);
    }

    private function applyWebhook(PaymentWebhookEvent $event, array $payload): void
    {
        $status = strtoupper((string) (data_get($payload, 'transaction.status') ?? data_get($payload, 'latestTransactionStatus') ?? ''));
        $invoiceReference = (string) (data_get($payload, 'order.invoice_number') ?? data_get($payload, 'trxId') ?? data_get($payload, 'partnerReferenceNo') ?? '');
        $invoiceId = $this->extractInvoiceId($invoiceReference);

        if (! $invoiceId) {
            throw new \RuntimeException('Referensi invoice DOKU tidak dapat dipetakan.');
        }

        $invoice = BillingInvoice::with('member')->findOrFail($invoiceId);
        $amount = (int) round((float) (data_get($payload, 'order.amount') ?? data_get($payload, 'paidAmount.value') ?? data_get($payload, 'amount.value') ?? 0));

        if ($amount > 0 && $amount !== (int) $invoice->amount) {
            throw new \RuntimeException('Nominal pembayaran DOKU tidak sesuai invoice.');
        }

        $attempt = $invoice->paymentAttempts()
            ->where(function ($q) use ($invoiceReference, $event) {
                $q->where('provider_reference', $invoiceReference)
                    ->orWhere('metadata->request_id', $event->provider_event_id);
            })
            ->latest()
            ->first();

        if (! $attempt) {
            $attempt = $invoice->paymentAttempts()->where('status', 'pending')->latest()->first();
        }

        if (! $attempt) {
            throw new \RuntimeException('Payment attempt untuk invoice tidak ditemukan.');
        }

        if ($status === 'SUCCESS' || $status === 'PAID' || $status === '00') {
            $this->settlePaidAttempt($attempt, $payload);
            return;
        }

        if (in_array($status, ['FAILED', 'EXPIRED', 'CANCELLED'], true)) {
            $attempt->update([
                'status' => strtolower($status),
                'failure_reason' => data_get($payload, 'transaction.responseMessage') ?? data_get($payload, 'responseMessage'),
            ]);
        }
    }

    private function settlePaidAttempt(PaymentAttempt $attempt, array $payload): void
    {
        DB::transaction(function () use ($attempt, $payload) {
            $attempt = PaymentAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            $invoice = BillingInvoice::query()->with('member')->lockForUpdate()->findOrFail($attempt->invoice_id);

            if ($attempt->status === 'paid' && $invoice->status === 'paid') {
                return;
            }

            if ((int) $attempt->amount !== (int) $invoice->amount) {
                throw new \RuntimeException('Nominal payment attempt tidak sama dengan invoice.');
            }

            $paidAt = data_get($payload, 'transaction.date')
                ?? data_get($payload, 'trxDateTime')
                ?? now()->toIso8601String();

            $payment = Payment::firstOrCreate(
                [
                    'invoice_id' => $invoice->id,
                    'external_transaction_id' => (string) (data_get($payload, 'transaction.original_request_id') ?? data_get($payload, 'referenceNo') ?? $attempt->provider_reference ?? $attempt->id),
                ],
                [
                    'amount' => $invoice->amount,
                    'paid_at' => Carbon::parse($paidAt),
                    'payment_method' => $attempt->channel,
                    'gateway' => 'doku',
                    'provider_reference' => $attempt->provider_reference,
                    'gateway_status' => 'SUCCESS',
                    'status' => 'paid',
                    'notes' => 'DOKU webhook',
                    'metadata' => $payload,
                ]
            );

            $invoice->update(['status' => 'paid']);
            $attempt->update([
                'status' => 'paid',
                'paid_at' => $payment->paid_at,
                'provider_transaction_id' => $payment->external_transaction_id,
                'provisioning_status' => 'pending',
            ]);

            $this->provisionAfterPayment($invoice, $attempt);
        });
    }

    private function provisionAfterPayment(BillingInvoice $invoice, PaymentAttempt $attempt): void
    {
        $member = $invoice->member;
        $prevStatus = $member->status;

        try {
            $newExpiredAt = Carbon::parse($invoice->period_end);
            $member->update([
                'expired_at' => $newExpiredAt,
                'status' => 'active',
            ]);

            if ($member->wasRecentlyIsolated()) {
                DB::connection('radius')->table('radcheck')
                    ->where('username', $member->username)
                    ->where('attribute', 'Auth-Type')
                    ->delete();
            }

            ServiceActionLog::record(
                'member',
                $member->id,
                'renew',
                $prevStatus,
                'active',
                null,
                'Renew otomatis dari pembayaran DOKU invoice #' . $invoice->id
            );

            $attempt->update([
                'provisioning_status' => 'success',
                'provisioning_error' => null,
            ]);
        } catch (Throwable $e) {
            $attempt->update([
                'provisioning_status' => 'failed',
                'provisioning_error' => $e->getMessage(),
            ]);

            report($e);
        }
    }

    private function extractInvoiceId(string $reference): ?int
    {
        if (preg_match('/(?:^|-)INV-(\d+)(?:-|$)/', $reference, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/^INV-(\d+)$/', $reference, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function safeHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $key => $value) {
            $lower = strtolower((string) $key);
            $result[$key] = in_array($lower, ['authorization', 'x-signature'], true) ? '[REDACTED]' : $value;
        }
        return $result;
    }
}
