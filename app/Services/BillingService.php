<?php

namespace App\Services;

use App\Models\BillingInvoice;
use App\Models\Member;
use App\Models\Payment;
use App\Models\ServiceActionLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BillingService
{
    /**
     * Buat invoice baru untuk member.
     * Period dihitung dari expired_at member saat ini.
     */
    public function createInvoice(Member $member, array $data = []): BillingInvoice
    {
        $periodStart = $member->expired_at
            ? Carbon::parse($member->expired_at)
            : now();

        $durationDays = $member->plan->duration_days ?? 30;
        $periodEnd    = $periodStart->copy()->addDays($durationDays);
        $dueDate      = isset($data['due_date'])
            ? Carbon::parse($data['due_date'])
            : now()->addDays(7);

        return BillingInvoice::create([
            'member_id'    => $member->id,
            'period_start' => $periodStart->toDateString(),
            'period_end'   => $periodEnd->toDateString(),
            'amount'       => $data['amount'] ?? $member->price_snapshot,
            'status'       => 'pending',
            'due_date'     => $dueDate->toDateString(),
            'notes'        => $data['notes'] ?? null,
        ]);
    }

    /**
     * Catat pembayaran manual, update status invoice → paid.
     */
    public function recordPayment(BillingInvoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $payment = Payment::create([
                'invoice_id'              => $invoice->id,
                'amount'                  => $data['amount'],
                'paid_at'                 => isset($data['paid_at']) ? Carbon::parse($data['paid_at']) : now(),
                'payment_method'          => $data['payment_method'] ?? 'cash',
                'notes'                   => $data['notes'] ?? null,
                'external_transaction_id' => null,
                'gateway_status'          => null,
            ]);

            $invoice->update(['status' => 'paid']);

            return $payment;
        });
    }

    /**
     * Perpanjang member — hitung dari expired_at lama (period_end invoice).
     * Jika invoice pending/overdue ditemukan, tandai paid sekaligus.
     */
    public function renewMember(Member $member, BillingInvoice $invoice, array $paymentData): void
    {
        DB::transaction(function () use ($member, $invoice, $paymentData) {
            $prevStatus = $member->status;

            // Catat pembayaran & tutup invoice
            $this->recordPayment($invoice, $paymentData);

            // Hitung expired baru dari period_end invoice
            $newExpiredAt = Carbon::parse($invoice->period_end);

            $member->update([
                'expired_at' => $newExpiredAt,
                'status'     => 'active',
            ]);

            // Jika sebelumnya isolir, pulihkan di RADIUS
            if ($member->wasRecentlyIsolated()) {
                DB::table('radcheck')
                    ->where('username', $member->username)
                    ->where('attribute', 'Auth-Type')
                    ->delete();
            }

            // FIX: gunakan positional arguments, bukan named arguments
            // Named argument 'entity_type:' tidak cocok dengan parameter '$entityType'
            ServiceActionLog::record(
                'member',           // entityType
                $member->id,        // entityId
                'renew',            // action
                $prevStatus,        // prev
                'active',           // next
                auth('app')->id(),  // userId
                null                // notes
            );
        });
    }

    /**
     * Override status invoice.
     */
    public function updateStatus(BillingInvoice $invoice, string $status): BillingInvoice
    {
        $invoice->update(['status' => $status]);
        return $invoice->fresh();
    }

    /**
     * Tandai semua invoice pending yang melewati due_date → overdue.
     * Dipanggil oleh Scheduler dan BillingController::index().
     */
    public function markOverdue(): int
    {
        return BillingInvoice::query()
            ->where('status', 'pending')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);
    }
}
