<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ConfigureJakartaTimezoneCommand extends Command
{
    protected $signature = 'app:configure-jakarta-timezone';

    protected $description = 'Set timezone aplikasi dan default timezone database FreeRADIUS ke Asia/Jakarta.';

    public function handle(): int
    {
        if (config('app.timezone') !== 'Asia/Jakarta') {
            $this->error('APP_TIMEZONE harus disetel ke Asia/Jakarta di .env sebelum menjalankan perintah ini.');

            return self::FAILURE;
        }

        try {
            $connection = DB::connection('radius');

            if ($connection->getDriverName() !== 'pgsql') {
                throw new RuntimeException('Koneksi radius harus menggunakan PostgreSQL.');
            }

            $database = $connection->getDatabaseName();
            if (! is_string($database) || $database === '') {
                throw new RuntimeException('Nama database koneksi radius tidak ditemukan.');
            }

            $quotedDatabase = '"' . str_replace('"', '""', $database) . '"';
            $quotedTimezone = $connection->getPdo()->quote('Asia/Jakarta');
            $connection->getPdo()->exec("ALTER DATABASE {$quotedDatabase} SET timezone TO {$quotedTimezone}");
        } catch (Throwable $exception) {
            $this->error('Gagal mengatur timezone database FreeRADIUS: ' . $exception->getMessage());
            $this->line('Pastikan user koneksi RADIUS adalah pemilik database atau jalankan ALTER DATABASE sebagai administrator PostgreSQL.');

            return self::FAILURE;
        }

        $this->info("Timezone database '{$database}' disetel ke Asia/Jakarta.");
        $this->line('Pengaturan berlaku untuk koneksi baru. Setup FreeRADIUS akan memuat ulang servicenya.');

        return self::SUCCESS;
    }
}
