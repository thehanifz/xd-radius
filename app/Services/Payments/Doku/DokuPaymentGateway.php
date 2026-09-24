<?php

namespace App\Services\Payments\Doku;

use App\Contracts\Payments\PaymentGateway;
use App\Models\BillingInvoice;
use App\Models\Member;
use App\Models\PaymentAccount;
use Illuminate\Support\Str;

class DokuPaymentGateway implements PaymentGateway
{
    public function __construct(private readonly DokuClient $client) {}

    public function createPaymentAttempt(BillingInvoice $invoice, string $method): array
    {
        return match ($method) {
            'va' => $this->createInvoiceVa($invoice),
            'qris' => $this->createQris($invoice),
            default => throw new DokuException('Metode pembayaran DOKU tidak didukung: ' . $method),
        };
    }

    public function createReusableVirtualAccount(Member $member, string $bank = 'BNI'): array
    {
        $bank = strtoupper($bank);
        $channel = match ($bank) {
            'BNI' => 'VIRTUAL_ACCOUNT_BNI',
            default => throw new DokuException('Channel VA DOKU belum dikonfigurasi untuk bank ' . $bank),
        };

        $partnerServiceId = config('doku.va_partner_service_id');
        if (! filled($partnerServiceId)) {
            throw new DokuException('DOKU_VA_PARTNER_SERVICE_ID belum dikonfigurasi.');
        }

        $customerNo = $this->customerNumber($member->id);
        $virtualAccountNo = $this->buildVirtualAccountNumber($partnerServiceId, $customerNo);
        $trxId = 'MEM-' . $member->id . '-' . Str::lower(Str::random(10));

        $payload = [
            'partnerServiceId' => $partnerServiceId,
            'customerNo' => $customerNo,
            'virtualAccountNo' => $virtualAccountNo,
            'virtualAccountName' => Str::limit($member->username, 255, ''),
            'trxId' => $trxId,
            'totalAmount' => [
                'value' => number_format((int) $member->price_snapshot, 2, '.', ''),
                'currency' => 'IDR',
            ],
            'additionalInfo' => [
                'channel' => $channel,
                'virtualAccountConfig' => [
                    'reusableStatus' => true,
                ],
            ],
            'virtualAccountTrxType' => 'C',
        ];

        $response = $this->client->postSnap(
            '/virtual-accounts/bi-snap-va/v1.1/transfer-va/create-va',
            $payload,
            config('doku.channel_id', 'H2H'),
        );

        $responseVa = data_get($response, 'virtualAccountData.virtualAccountNo')
            ?? data_get($response, 'virtualAccountData.virtualAccountNumber');

        if (! filled($responseVa)) {
            throw new DokuException('DOKU tidak mengembalikan nomor Virtual Account.', $response);
        }

        return [
            'bank' => $bank,
            'channel' => $channel,
            'provider_account_id' => $trxId,
            'provider_customer_id' => $customerNo,
            'account_number' => (string) $responseVa,
            'status' => 'active',
            'metadata' => $response,
        ];
    }

    public function verifyWebhook(array $headers, string $rawBody, string $path): bool
    {
        $timestamp = $headers['x-timestamp'] ?? $headers['X-TIMESTAMP'] ?? null;
        $signature = $headers['x-signature'] ?? $headers['X-SIGNATURE'] ?? null;
        $partnerId = $headers['x-partner-id'] ?? $headers['X-PARTNER-ID'] ?? null;
        if (! filled($timestamp) || ! filled($signature)) {
            return false;
        }
        if (filled($partnerId) && filled(config('doku.client_id')) && ! hash_equals((string) config('doku.client_id'), (string) $partnerId)) {
            return false;
        }

        $expected = DokuSigner::snapRequestSignature('POST', $path, $rawBody, $timestamp, (string) config('doku.secret_key'));
        return hash_equals($expected, $signature);
    }

    private function createInvoiceVa(BillingInvoice $invoice): array
    {
        $account = $invoice->member->paymentAccounts()->where('status', 'active')->orderByDesc('is_default')->first();
        if (! $account) {
            $created = $this->createReusableVirtualAccount($invoice->member, config('doku.va_bank', 'BNI'));
            $account = new PaymentAccount($created);
        }

        $trxId = 'INV-' . $invoice->id . '-' . Str::lower(Str::random(8));
        $payload = [
            'partnerServiceId' => config('doku.va_partner_service_id'),
            'customerNo' => $account->provider_customer_id,
            'virtualAccountNo' => $account->account_number,
            'virtualAccountName' => Str::limit($invoice->member->username, 255, ''),
            'trxId' => $trxId,
            'totalAmount' => [
                'value' => number_format((int) $invoice->amount, 2, '.', ''),
                'currency' => 'IDR',
            ],
            'additionalInfo' => [
                'channel' => 'VIRTUAL_ACCOUNT_' . strtoupper($account->bank),
                'virtualAccountConfig' => [
                    'reusableStatus' => true,
                ],
            ],
            'virtualAccountTrxType' => 'C',
        ];

        $response = $this->client->postSnap(
            '/virtual-accounts/bi-snap-va/v1.1/transfer-va/update-va',
            $payload,
            config('doku.channel_id', 'H2H'),
        );

        return [
            'gateway' => 'doku',
            'channel' => 'va_' . strtolower($account->bank),
            'provider_reference' => $trxId,
            'payment_url' => null,
            'qr_content' => null,
            'expires_at' => null,
            'metadata' => $response,
        ];
    }

    private function createQris(BillingInvoice $invoice): array
    {
        $merchantId = config('doku.merchant_id');
        $terminalId = config('doku.terminal_id');
        if (! filled($merchantId) || ! filled($terminalId)) {
            throw new DokuException('DOKU QRIS membutuhkan DOKU_MERCHANT_ID dan DOKU_TERMINAL_ID.');
        }

        $expires = now()->addMinutes(30)->format('Y-m-d\\TH:i:sP');
        $payload = [
            'partnerReferenceNo' => 'INV-' . $invoice->id . '-' . Str::upper(Str::random(8)),
            'amount' => [
                'value' => number_format((int) $invoice->amount, 2, '.', ''),
                'currency' => 'IDR',
            ],
            'merchantId' => (string) $merchantId,
            'terminalId' => (string) $terminalId,
            'validityPeriod' => $expires,
            'additionalInfo' => [
                'feeType' => '1',
            ],
        ];

        $response = $this->client->postSnap('/snap-adapter/b2b/v1.0/qr/qr-mpm-generate', $payload, 'H2H');
        $qr = data_get($response, 'qrContent');
        if (! filled($qr)) {
            throw new DokuException('DOKU tidak mengembalikan QR content.', $response);
        }

        return [
            'gateway' => 'doku',
            'channel' => 'qris',
            'provider_reference' => $payload['partnerReferenceNo'],
            'payment_url' => null,
            'qr_content' => $qr,
            'expires_at' => $expires,
            'metadata' => $response,
        ];
    }

    private function customerNumber(int $memberId): string
    {
        $prefix = preg_replace('/\\D+/', '', (string) config('doku.va_customer_prefix', '3'));
        return $prefix . str_pad((string) $memberId, 10, '0', STR_PAD_LEFT);
    }

    private function buildVirtualAccountNumber(string $partnerServiceId, string $customerNo): string
    {
        return str_pad(preg_replace('/\\D+/', '', $partnerServiceId), 8, '0', STR_PAD_LEFT) . $customerNo;
    }
}
