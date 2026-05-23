<?php

namespace App\Jobs;

use App\Models\BillingInvoice;
use App\Models\Member;
use App\Models\Radcheck;
use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AutoIsolateOverdueMembersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $autoIsolate = SystemSetting::get('overdue_isolate_auto', false);
        if (! $autoIsolate) return;

        $overdueInvoices = BillingInvoice::where('status', 'overdue')
            ->with('member')
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $member = $invoice->member;
            if (! $member || $member->status === 'isolated') continue;

            DB::transaction(function () use ($member) {
                Radcheck::where('username', $member->username)
                    ->where('attribute', 'Auth-Type')
                    ->delete();

                Radcheck::create([
                    'username'  => $member->username,
                    'attribute' => 'Auth-Type',
                    'op'        => ':=',
                    'value'     => 'Reject',
                ]);

                $member->update(['status' => 'isolated']);

                \App\Models\ServiceActionLog::create([
                    'entity_type'     => 'member',
                    'entity_id'       => $member->id,
                    'action'          => 'auto_isolate_overdue',
                    'previous_status' => 'active',
                    'new_status'      => 'isolated',
                    'performed_by'    => null,
                    'performed_at'    => now(),
                    'notes'           => 'Auto isolir karena tagihan overdue',
                ]);
            });
        }
    }
}
