<?php

namespace App\Services\Payments\Doku;

use App\Models\DokuSetting;
use Illuminate\Support\Facades\Schema;

class DokuSettingsService
{
    public function current(): DokuSetting
    {
        return DokuSetting::current();
    }

    public function config(): array
    {
        return $this->current()->toConfig();
    }

    public function applyToConfig(): void
    {
        if (! Schema::hasTable('doku_settings')) {
            return;
        }

        config(['doku' => array_replace(config('doku'), $this->config())]);
    }

    public function save(array $data): DokuSetting
    {
        $setting = $this->current();

        foreach (['secret_key', 'api_key', 'private_key_passphrase'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null) {
                unset($data[$field]);
            }
        }

        $setting->fill($data);
        $setting->save();

        $this->applyToConfig();

        return $setting->refresh();
    }

    public function keyStatus(): array
    {
        $setting = $this->current();
        $private = $setting->private_key_path;
        $public = $setting->public_key_path;

        $fingerprint = $setting->key_fingerprint ?: $this->fingerprint($public);
        if ($fingerprint && $setting->key_fingerprint !== $fingerprint) {
            $setting->forceFill(['key_fingerprint' => $fingerprint])->save();
        }

        return [
            'private_exists' => filled($private) && is_readable($private),
            'public_exists' => filled($public) && is_readable($public),
            'fingerprint' => $fingerprint,
            'private_path' => $private,
            'public_path' => $public,
        ];
    }

    public function fingerprint(?string $publicPath = null): ?string
    {
        $path = $publicPath ?: $this->current()->public_key_path;
        if (! $path || ! is_readable($path)) {
            return null;
        }

        $command = sprintf('openssl pkey -pubin -in %s -outform DER 2>/dev/null | sha256sum', escapeshellarg($path));
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        if ($exitCode !== 0 || empty($output[0])) {
            return null;
        }

        $hash = trim((string) preg_replace('/\s+.*$/', '', $output[0]));
        return $hash !== '' ? 'SHA256:' . $hash : null;
    }
}
