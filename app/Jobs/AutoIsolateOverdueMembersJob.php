<?php

namespace App\Jobs;

use App\Models\BillingInvoice;
use App\Models\Member;
use App\Models\ServiceActionLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoIsolateOverdueMembersJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Cari member aktif yang punya invoice overdue
        $memberIds = BillingInvoice::where('status', 'overdue')
            ->pluck('member_id')
            ->unique();

        $isolated = 0;

        foreach ($memberIds as $memberId) {
            $member = Member::find($memberId);

            // Skip jika member tidak ditemukan atau sudah isolir
            if (!$member || $member->status !== 'active') continue;

            try {
                DB::transaction(function () use ($member) {
                    // Tambah Auth-Type := Reject ke radcheck
                    DB::table('radcheck')->updateOrInsert(
                        [
                            'username'  => $member->username,
                            'attribute' => 'Auth-Type',
                        ],
                        [
                            'op'    => ':=',
                            'value' => 'Reject',
                        ]
                    );

                    $member->update(['status' => 'isolated']);

                    // Catat di service_action_logs
                    ServiceActionLog::record(
                        'member',
                        $member->id,
                        'isolate',
                        'active',
                        'isolated',
                        null, // system action, bukan user
                        'Auto-isolir karena invoice overdue'
                    );
                });

                $isolated++;

            } catch (\Throwable $e) {
                Log::error("AutoIsolateOverdueMembersJob: gagal isolir member #{$memberId}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("AutoIsolateOverdueMembersJob selesai.", [
            'total_overdue_members' => $memberIds->count(),
            'isolated' => $isolated,
        ]);
    }
}
