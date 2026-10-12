<?php

namespace App\Jobs;

use App\Models\Radacct;
use App\Models\Router;
use App\Models\SystemSetting;
use App\Services\RouterConnectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReconcileStaleSessionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(RouterConnectionService $connectionService): void
    {
        $thresholdMinutes = SystemSetting::get('stale_threshold_minutes', 30);
        $cutoff = now()->subMinutes($thresholdMinutes);

        $sessions = Radacct::query()
            ->whereNull('acctstoptime')
            ->where(fn($query) => $query
                ->where('is_stale', true)
                ->orWhere('acctupdatetime', '<', $cutoff))
            ->get(['radacctid', 'username', 'nasipaddress', 'is_stale']);

        foreach ($sessions->groupBy('nasipaddress') as $nasIp => $routerSessions) {
            if (! $nasIp) {
                continue;
            }

            $router = Router::withoutTrashed()
                ->where('ip_address', $nasIp)
                ->first();

            if (! $router) {
                continue;
            }

            try {
                $activeUsernames = $connectionService->activeUsernames($router);
            } catch (\Throwable $e) {
                Log::warning('Gagal memverifikasi sesi stale ke RouterOS.', [
                    'nas_ip' => $nasIp,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            foreach ($routerSessions as $session) {
                $isOnline = in_array($session->username, $activeUsernames, true);
                $isStale = ! $isOnline;

                if ((bool) $session->is_stale === $isStale) {
                    continue;
                }

                Radacct::where('radacctid', $session->radacctid)->update([
                    'is_stale' => $isStale,
                    'stale_detected_at' => $isStale ? now() : null,
                ]);
            }
        }
    }
}
