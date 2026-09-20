<?php

namespace App\Services\Radius;

use App\Models\Router;
use Illuminate\Support\Facades\DB;

class FreeRadiusNasManager
{
    public function desired(): array
    {
        return Router::query()->where('radius_enabled', true)->whereNotNull('radius_secret')->get()->map(fn (Router $r) => [
            'nasname' => $r->ip_address,
            'shortname' => $r->name,
            'type' => 'other',
            'secret' => $r->radius_secret,
            'description' => $r->location ?: $r->name,
            'server' => null,
            'community' => null,
            'ports' => 0,
        ])->all();
    }

    public function snapshot(): array
    {
        return DB::connection('radius')->table('nas')->get()->map(fn ($row) => (array) $row)->all();
    }

    public function apply(): void
    {
        $db = DB::connection('radius');
        foreach ($this->desired() as $nas) {
            $db->table('nas')->updateOrInsert(['nasname' => $nas['nasname']], $nas);
        }
        $desiredIps = collect($this->desired())->pluck('nasname')->all();
        if ($desiredIps === []) {
            $db->table('nas')->where('shortname', 'like', 'xd-radius:%')->delete();
        }
    }

    public function restore(array $snapshot): void
    {
        $db = DB::connection('radius');
        $db->table('nas')->delete();
        if ($snapshot !== []) $db->table('nas')->insert($snapshot);
    }
}
