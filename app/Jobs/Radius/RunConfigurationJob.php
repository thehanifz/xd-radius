<?php

namespace App\Jobs\Radius;

use App\Models\RadiusManagementOperation;
use App\Models\Router;
use App\Services\Radius\FreeRadiusConfigurationPipeline;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunConfigurationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function backoff(): array { return [10, 30, 60]; }

    public function __construct(public string $operation = 'CONFIG_UPDATE', public ?int $routerId = null) {}

    public function handle(FreeRadiusConfigurationPipeline $pipeline): void
    {
        $record = RadiusManagementOperation::create([
            'type' => $this->operation,
            'status' => 'running',
            'target_type' => $this->routerId ? Router::class : null,
            'target_id' => $this->routerId,
            'started_at' => now(),
        ]);
        $result = $pipeline->run($this->operation);
        $record->update([
            'status' => $result['ok'] ? 'success' : 'failed',
            'message' => $result['message'] ?? null,
            'details' => ['health' => $result['health'] ?? null],
            'finished_at' => now(),
        ]);

        if ($this->routerId) {
            $router = Router::withTrashed()->find($this->routerId);
            if ($router) {
                $router->updateQuietly([
                    'radius_sync_status' => $result['ok'] ? 'in_sync' : 'failed',
                    'radius_last_synced_at' => $result['ok'] ? now() : $router->radius_last_synced_at,
                    'radius_last_sync_error' => $result['ok'] ? null : ($result['message'] ?? 'Sinkronisasi gagal.'),
                ]);
            }
        }
    }
}
