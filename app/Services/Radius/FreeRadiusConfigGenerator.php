<?php

namespace App\Services\Radius;

use Illuminate\Support\Str;
use RuntimeException;

class FreeRadiusConfigGenerator
{
    private const BEGIN = '# BEGIN XD-RADIUS MANAGED BLOCK';
    private const END = '# END XD-RADIUS MANAGED BLOCK';

    public function __construct(private readonly FreeRadiusCommandRunner $runner) {}

    public function generate(): array
    {
        $environment = app(FreeRadiusEnvironment::class)->inspect();
        if (! $environment['installed'] || ! $environment['config_dir']) {
            throw new RuntimeException('FreeRADIUS belum terdeteksi atau config directory tidak ditemukan.');
        }
        $dir = $environment['config_dir'];
        $files = [];

        $files[$dir . '/mods-enabled/sql_app'] = $this->sqlAppModule();
        $sqlAvailable = $dir . '/mods-available/sql';
        if (! is_file($sqlAvailable)) throw new RuntimeException('FreeRADIUS SQL module tidak ditemukan: ' . $sqlAvailable);
        $sqlContent = file_get_contents($sqlAvailable);
        $sqlContent = preg_replace('/^\s*dialect\s*=.*$/m', 'dialect = "postgresql"', $sqlContent);
        $sqlContent = preg_replace('/^\s*driver\s*=.*$/m', 'driver = "rlm_sql_postgresql"', $sqlContent);
        $sqlContent = preg_replace('/^\s*radius_db\s*=.*$/m', 'radius_db = "' . addslashes($this->radiusConnectionString()) . '"', $sqlContent);
        $sqlContent = preg_replace('/^\s*#?\s*read_clients\s*=.*$/m', 'read_clients = yes', $sqlContent);
        if (! preg_match('/^\s*client_table\s*=/m', $sqlContent)) $sqlContent .= "\nclient_table = \"nas\"\n";
        $files[$sqlAvailable] = $sqlContent;

        $enabled = $dir . '/mods-enabled/sql';
        if (! is_file($enabled) && ! is_link($enabled)) {
            $this->runPrivileged('ln', ['-s', $sqlAvailable, $enabled]);
        }

        $default = $dir . '/sites-enabled/default';
        if (is_file($default)) {
            $content = file_get_contents($default);
            $content = preg_replace('/' . preg_quote(self::BEGIN, '/') . '.*?' . preg_quote(self::END, '/') . '\n?/s', '', $content);
            $files[$default] = $content;
        }

        foreach ($files as $target => $content) $this->writePrivileged($target, $content);

        return ['config_dir' => $dir, 'files' => array_keys($files)];
    }

    private function sqlAppModule(): string
    {
        $conn = sprintf(
            'host=%s port=%s dbname=%s user=%s password=%s sslmode=%s',
            env('DB_HOST', '127.0.0.1'), env('DB_PORT', '5432'), env('DB_DATABASE', 'laravel'),
            env('DB_USERNAME', 'root'), env('DB_PASSWORD', ''), env('DB_SSLMODE', 'prefer')
        );
        return "sql sql_app {\n    dialect = \"postgresql\"\n    driver = \"rlm_sql_postgresql\"\n    radius_db = \"" . addslashes($conn) . "\"\n    read_groups = no\n    read_profiles = no\n    read_clients = no\n}\n";
    }

    private function radiusConnectionString(): string
    {
        return sprintf(
            'host=%s port=%s dbname=%s user=%s password=%s sslmode=%s',
            env('RADIUS_DB_HOST', env('DB_HOST', '127.0.0.1')), env('RADIUS_DB_PORT', env('DB_PORT', '5432')),
            env('RADIUS_DB_DATABASE', env('DB_DATABASE', 'laravel')), env('RADIUS_DB_USERNAME', env('DB_USERNAME', 'root')),
            env('RADIUS_DB_PASSWORD', env('DB_PASSWORD', '')), env('RADIUS_DB_SSLMODE', env('DB_SSLMODE', 'prefer'))
        );
    }

    private function writePrivileged(string $target, string $content): void
    {
        // Privileged helper only accepts files from its dedicated staging directory.
        $staging = '/var/lib/xd-radius-freeradius/staging';
        if (! is_dir($staging)) mkdir($staging, 0750, true);
        $temp = $staging . '/' . Str::uuid() . '.conf';
        file_put_contents($temp, $content);
        $result = $this->runner->execute('/usr/bin/install', ['-m', '0640', $temp, $target], 30, true);
        @unlink($temp);
        if ($result['exit_code'] !== 0) throw new RuntimeException('Gagal menulis konfigurasi FreeRADIUS: ' . $target . ' (' . ($result['stderr'] ?: $result['stdout'] ?: 'exit code ' . $result['exit_code']) . ')');
    }

    private function runPrivileged(string $binary, array $args): void
    {
        $path = '/usr/bin/' . $binary;
        $result = $this->runner->execute($path, $args, 30, true);
        if ($result['exit_code'] !== 0) throw new RuntimeException('Gagal menjalankan operasi FreeRADIUS: ' . implode(' ', $args) . ' (' . ($result['stderr'] ?: $result['stdout'] ?: 'exit code ' . $result['exit_code']) . ')');
    }
}
