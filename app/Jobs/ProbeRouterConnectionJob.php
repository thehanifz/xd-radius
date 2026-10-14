<?php

namespace App\Jobs;

use App\Models\Router;
use App\Services\RouterConnectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProbeRouterConnectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 20;

    public function __construct(public int $routerId) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping('router-probe-' . $this->routerId))->expireAfter(90)->dontRelease()];
    }

    public function handle(RouterConnectionService $connectionService): void
    {
        $router = Router::active()->find($this->routerId);
        if (! $router) {
            return;
        }

        $connectionService->ping($router);
    }
}
