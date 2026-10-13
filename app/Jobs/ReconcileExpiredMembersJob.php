<?php

namespace App\Jobs;

use App\Models\Member;
use App\Models\Radacct;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReconcileExpiredMembersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Member::query()
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->select(['id', 'username', 'status'])
            ->chunkById(100, function ($members): void {
                foreach ($members as $member) {
                    // Re-run expiry for already-expired members when accounting
                    // still reports a live session (e.g. a previous API failure).
                    $hasActiveSession = Radacct::query()
                        ->where('username', $member->username)
                        ->whereNull('acctstoptime')
                        ->exists();

                    if ($member->status !== 'expired' || $hasActiveSession) {
                        ExpireMemberJob::dispatch($member->id);
                    }
                }
            });
    }
}