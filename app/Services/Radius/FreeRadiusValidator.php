<?php

namespace App\Services\Radius;

class FreeRadiusValidator
{
    public function __construct(private readonly FreeRadiusCommandRunner $runner) {}

    public function validate(?string $binary): array
    {
        if (! $binary) return ['ok' => false, 'message' => 'FreeRADIUS binary tidak ditemukan.', 'output' => ''];
        $result = $this->runner->execute($binary, ['-XC'], 60);
        return ['ok' => $result['exit_code'] === 0, 'message' => $result['exit_code'] === 0 ? null : 'Validasi konfigurasi FreeRADIUS gagal.', 'output' => trim($result['stdout'] . "\n" . $result['stderr'])];
    }
}
