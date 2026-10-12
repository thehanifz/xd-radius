<?php

namespace App\Jobs;

use App\Models\Member;
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
            ->where('status', '!=', 'expired')
            ->select('id')
            ->chunkById(100, function ($members): void {
                foreach ($members as $member) {
                    ExpireMemberJob::dispatch($member->id);
                }
            });
    }
}