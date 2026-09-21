<?php

namespace App\Console\Commands;

use App\Services\Radius\FreeRadiusHealthChecker;
use Illuminate\Console\Command;
use Throwable;

class FreeRadiusHealthCheckCommand extends Command
{
    protected $signature = 'freeradius:health-check';

    protected $description = 'Jalankan FreeRadiusHealthChecker dan keluar dengan exit code yang bersih (0 = healthy, 1 = not healthy).';

    public function handle(FreeRadiusHealthChecker $checker): int
    {
        try {
            $result = $checker->check();
        } catch (Throwable $e) {
            $this->error('HEALTH_CHECK_FAILED: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $healthy = $result['healthy'] ?? $result['ok'] ?? false;
        if (! $healthy) {
            $this->error('HEALTH_CHECK_FAILED: FreeRADIUS tidak healthy.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
