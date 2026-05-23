<?php

namespace App\Jobs;

use App\Models\BillingInvoice;
use App\Models\Member;
use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateOverdueInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $daysBefore = SystemSetting::get('invoice_days_before', 7);

        // Mark existing invoices as overdue
        BillingInvoice::where('status', 'pending')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        // Create new invoices for active members whose due_date is approaching
        Member::where('status', 'active')
            ->whereNotNull('expired_at')
            ->get()
            ->each(function ($member) use ($daysBefore) {
                $period = $member->expired_at->format('Y-m');

                $exists = BillingInvoice::where('member_id', $member->id)
                    ->where('period_start', $member->expired_at->copy()->startOfMonth())
                    ->exists();

                if (! $exists && $member->expired_at->lte(now()->addDays($daysBefore))) {
                    BillingInvoice::create([
                        'member_id'    => $member->id,
                        'period_start' => $member->expired_at->copy()->startOfMonth(),
                        'period_end'   => $member->expired_at->copy()->endOfMonth(),
                        'amount'       => $member->price_snapshot,
                        'status'       => 'pending',
                        'due_date'     => $member->expired_at->toDateString(),
                    ]);
                }
            });
    }
}
