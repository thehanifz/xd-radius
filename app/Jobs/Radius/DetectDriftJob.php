<?php

namespace App\Jobs\Radius;

use App\Services\Radius\FreeRadiusAuditService;
use App\Services\Radius\FreeRadiusDriftDetector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DetectDriftJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function handle(FreeRadiusDriftDetector $detector, FreeRadiusAuditService $audit): void
    {
        $drift = $detector->detect();
        if (collect($drift)->contains(fn ($value) => $value['drift'] ?? false)) $audit->record('DRIFT_DETECTED', 'pending', ['types' => $drift]);
    }
}
