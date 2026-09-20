<?php

namespace App\Services\Radius;

use Illuminate\Support\Str;
use RuntimeException;

class FreeRadiusBackupManager
{
    public function backup(?string $configDir): ?string
    {
        if (! $configDir || ! is_dir($configDir)) return null;
        $root = storage_path('app/freeradius/backups/' . now()->format('Ymd_His') . '_' . Str::lower(Str::random(8)));
        if (! mkdir($root, 0750, true) && ! is_dir($root)) throw new RuntimeException('Tidak dapat membuat direktori backup FreeRADIUS.');

        $files = $this->managedFiles($configDir);
        $manifest = [];
        foreach ($files as $file) {
            $manifest[$file] = is_file($file) || is_link($file);
            if (! is_file($file)) continue;
            $relative = ltrim(str_replace($configDir, '', $file), '/');
            $target = $root . '/' . $relative;
            if (! is_dir(dirname($target))) mkdir(dirname($target), 0750, true);
            copy($file, $target);
        }
        file_put_contents($root . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $root;
    }

    public function restore(string $backup, string $configDir): void
    {
        if (! is_dir($backup)) throw new RuntimeException('Backup tidak ditemukan.');
        $manifestFile = $backup . '/manifest.json';
        if (is_file($manifestFile)) {
            $manifest = json_decode(file_get_contents($manifestFile), true) ?: [];
            foreach ($manifest as $original => $existed) {
                if (! $existed && (is_file($original) || is_link($original))) @unlink($original);
            }
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($backup, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (! $file->isFile()) continue;
            $relative = ltrim(str_replace($backup, '', $file->getPathname()), '/');
            $target = $configDir . '/' . $relative;
            if (! is_dir(dirname($target))) mkdir(dirname($target), 0750, true);
            copy($file->getPathname(), $target);
        }
    }

    private function managedFiles(string $configDir): array
    {
        return [
            $configDir . '/mods-available/sql',
            $configDir . '/mods-enabled/sql',
            $configDir . '/mods-enabled/sql_app',
            $configDir . '/mods-enabled/xd_radius_sql',
            $configDir . '/sites-enabled/default',
            $configDir . '/sites-enabled/inner-tunnel',
        ];
    }
}
