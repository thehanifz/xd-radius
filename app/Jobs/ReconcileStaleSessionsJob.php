<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReconcileStaleSessionsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Ambil threshold dari settings (default 10 menit)
        // Bisa dikonfigurasi super user dari halaman pengaturan nantinya
        $thresholdMinutes = (int) config('radius.stale_threshold_minutes', 10);

        $cutoff = now()->subMinutes($thresholdMinutes);

        // Tandai sesi yang:
        // 1. Masih "aktif" (acctstoptime IS NULL)
        // 2. Tidak di-update melebihi threshold
        // 3. Belum ditandai stale sebelumnya
        $updated = DB::table('radacct')
            ->whereNull('acctstoptime')
            ->whereNull('stale_detected_at')
            ->where(function ($q) use ($cutoff) {
                $q->where('acctupdatetime', '<', $cutoff)
                  ->orWhereNull('acctupdatetime'); // Tidak pernah ada update sama sekali
            })
            ->update([
                'is_stale'          => true,
                'stale_detected_at' => now(),
            ]);

        if ($updated > 0) {
            Log::info("ReconcileStaleSessionsJob: {$updated} sesi ditandai stale.", [
                'threshold_minutes' => $thresholdMinutes,
                'cutoff'            => $cutoff->toDateTimeString(),
            ]);
        }
    }
}
