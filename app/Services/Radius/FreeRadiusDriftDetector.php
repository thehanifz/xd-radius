<?php

namespace App\Services\Radius;

use App\Models\Router;
use Illuminate\Support\Facades\DB;

class FreeRadiusDriftDetector
{
    public function __construct(private readonly FreeRadiusEnvironment $environment) {}

    public function detect(): array
    {
        return [
            'file' => $this->fileDrift(),
            'nas' => $this->nasDrift(),
            'desired_applied' => $this->desiredAppliedDrift(),
        ];
    }

    private function fileDrift(): array
    {
        $dir = $this->environment->configDir();
        if (! $dir) return ['drift' => true, 'message' => 'Config directory tidak ditemukan.'];
        $targets = [$dir . '/mods-enabled/sql_app', $dir . '/mods-enabled/sql', $dir . '/sites-enabled/default'];
        foreach ($targets as $file) if (! is_file($file) && ! is_link($file)) return ['drift' => true, 'message' => 'Managed FreeRADIUS file hilang: ' . $file];
        return ['drift' => false];
    }

    private function nasDrift(): array
    {
        $db = DB::connection('radius');
        $actual = $db->table('nas')->get(['nasname', 'shortname', 'secret', 'description'])->map(fn ($r) => implode('|', [(string)$r->nasname, (string)$r->shortname, (string)$r->secret, (string)$r->description]))->sort()->values()->all();
        $desired = Router::query()->where('radius_enabled', true)->whereNotNull('radius_secret')->get()->map(fn (Router $r) => implode('|', [$r->ip_address, $r->name, $r->radius_secret, $r->location ?: $r->name]))->sort()->values()->all();
        return ['drift' => $actual !== $desired, 'expected_count' => count($desired), 'actual_count' => count($actual)];
    }

    private function desiredAppliedDrift(): array
    {
        $routers = Router::query()->where('radius_enabled', true)->whereNotNull('radius_secret')->get();
        $pending = $routers->filter(fn (Router $r) => $r->radius_sync_status !== 'in_sync')->pluck('id')->values()->all();
        return ['drift' => $pending !== [], 'router_ids' => $pending];
    }
}
