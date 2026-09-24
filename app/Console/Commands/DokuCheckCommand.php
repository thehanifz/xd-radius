<?php

namespace App\Console\Commands;

use App\Services\Payments\Doku\DokuClient;
use Illuminate\Console\Command;
use Throwable;

class DokuCheckCommand extends Command
{
    protected $signature = 'doku:check';
    protected $description = 'Validate DOKU SNAP credentials and obtain a short-lived B2B token.';

    public function handle(): int
    {
        try {
            $token = app(DokuClient::class)->accessToken();
            $this->info('DOKU credentials OK. B2B token berhasil diperoleh.');
            $this->line('Token TTL is managed internally and is not displayed.');
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('DOKU check gagal: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
