<?php

namespace App\Jobs;

use App\Models\BillingInvoice;
use App\Models\Member;
use App\Services\BillingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateOverdueInvoicesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(protected BillingService $billing) {}

    public function handle(): void
    {
        // Tandai invoice pending yang sudah melewati due_date → overdue
        $markedOverdue = $this->billing->markOverdue();

        // Generate invoice baru untuk member aktif yang akan jatuh tempo dalam 7 hari
        // dan belum punya invoice pending/overdue untuk periode berikutnya
        $targetDate = now()->addDays(7)->toDateString();

        $members = Member::with('plan')
            ->where('status', 'active')
            ->whereNotNull('expired_at')
            ->whereDate('expired_at', '<=', $targetDate)
            ->get();

        $generated = 0;

        foreach ($members as $member) {
            // Cek apakah sudah ada invoice pending/overdue yang belum dibayar
            $hasOpenInvoice = BillingInvoice::where('member_id', $member->id)
                ->whereIn('status', ['pending', 'overdue'])
                ->exists();

            if ($hasOpenInvoice) continue;

            try {
                $this->billing->createInvoice($member);
                $generated++;
            } catch (\Throwable $e) {
                Log::error("GenerateOverdueInvoicesJob: gagal buat invoice untuk member #{$member->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("GenerateOverdueInvoicesJob selesai.", [
            'marked_overdue' => $markedOverdue,
            'invoices_generated' => $generated,
        ]);
    }
}
