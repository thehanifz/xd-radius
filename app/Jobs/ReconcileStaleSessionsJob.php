<?php

namespace App\Jobs;

use App\Models\Radacct;
use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReconcileStaleSessionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $thresholdMinutes = SystemSetting::get('stale_threshold_minutes', 30);

        Radacct::whereNull('acctstoptime')
            ->where(fn($q) => $q->where('is_stale', false)->orWhereNull('is_stale'))
            ->where('acctupdatetime', '<', now()->subMinutes($thresholdMinutes))
            ->update([
                'is_stale'          => true,
                'stale_detected_at' => now(),
            ]);
    }
}
