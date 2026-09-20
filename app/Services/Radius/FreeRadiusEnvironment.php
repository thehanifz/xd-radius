<?php

namespace App\Services\Radius;

use Illuminate\Support\Facades\DB;
use Throwable;

class FreeRadiusEnvironment
{
    public function __construct(private readonly FreeRadiusCommandRunner $runner) {}

    public function inspect(): array
    {
        $binary = $this->binary();
        $version = null;
        $versionResult = null;
        if ($binary) {
            $versionResult = $this->runner->execute($binary, ['-v']);
            if ($versionResult['exit_code'] === 0) {
                if (preg_match('/FreeRADIUS Version\s+([0-9.]+)/i', $versionResult['stdout'], $m)) $version = $m[1];
                elseif (preg_match('/radiusd:\s+version\s+([0-9.]+)/i', $versionResult['stdout'], $m)) $version = $m[1];
            }
        }

        $service = $this->runner->execute('/bin/systemctl', ['is-active', 'freeradius'], 30, true);
        if ($service['exit_code'] !== 0) {
            $service = $this->runner->execute('/usr/bin/systemctl', ['is-active', 'freeradius'], 30, true);
        }

        return [
            'installed' => $binary !== null,
            'binary' => $binary,
            'version' => $version,
            'service_active' => $service['exit_code'] === 0,
            'service_output' => $service['stdout'] ?: $service['stderr'],
            'config_dir' => $this->configDir($version),
            'database' => $this->databaseCheck(),
            'privileged_access' => $this->privilegedAccessCheck(),
        ];
    }

    public function binary(): ?string
    {
        foreach (['/usr/sbin/freeradius', '/usr/sbin/radiusd', '/usr/bin/freeradius', '/usr/bin/radiusd'] as $path) {
            if (is_executable($path)) return $path;
        }
        return null;
    }

    public function configDir(?string $version = null): ?string
    {
        $candidates = [];
        if ($version) {
            $major = implode('.', array_slice(explode('.', $version), 0, 2));
            $candidates[] = "/etc/freeradius/{$major}";
        }
        $candidates = array_merge($candidates, ['/etc/freeradius/3.0', '/etc/freeradius/3.2', '/etc/freeradius']);
        foreach ($candidates as $dir) if (is_dir($dir)) return $dir;
        return null;
    }

    private function privilegedAccessCheck(): array
    {
        try {
            $result = $this->runner->execute('/bin/systemctl', ['is-active', 'freeradius'], 15, true);
            return [
                'ok' => $result['exit_code'] === 0,
                'helper' => env('FREERADIUS_PRIVILEGED_HELPER', '/usr/local/sbin/xd-radius-freeradius'),
                'message' => $result['exit_code'] === 0 ? null : ($result['stderr'] ?: 'Privileged helper tidak dapat dipanggil.'),
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'helper' => env('FREERADIUS_PRIVILEGED_HELPER', '/usr/local/sbin/xd-radius-freeradius'),
                'message' => $e->getMessage(),
            ];
        }
    }

    private function databaseCheck(): array
    {
        try {
            DB::connection('radius')->getPdo();
            return ['ok' => true, 'message' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
