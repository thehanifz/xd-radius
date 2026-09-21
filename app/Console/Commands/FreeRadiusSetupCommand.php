<?php

namespace App\Console\Commands;

use App\Services\Radius\FreeRadiusSetupService;
use Illuminate\Console\Command;
use Throwable;

class FreeRadiusSetupCommand extends Command
{
    protected $signature = 'freeradius:setup';

    protected $description = 'Jalankan FreeRadiusSetupService (bootstrap schema + configuration pipeline SETUP) dan keluar dengan exit code yang bersih.';

    public function handle(FreeRadiusSetupService $setup): int
    {
        try {
            $result = $setup->run();
        } catch (Throwable $e) {
            $this->error('FREERADIUS_SETUP_FAILED: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        if (! ($result['ok'] ?? false)) {
            $this->error('FREERADIUS_SETUP_FAILED: ' . ($result['message'] ?? 'Setup gagal tanpa pesan detail.'));
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
