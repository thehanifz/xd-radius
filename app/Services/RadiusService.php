<?php

namespace App\Services;

use App\Models\Radcheck;
use App\Models\Radreply;
use App\Models\Radusergroup;
use App\Models\Plan;

class RadiusService
{
    /**
     * Provision user baru ke FreeRADIUS (radcheck + radreply + radusergroup)
     * Harus dipanggil di dalam DB::transaction()
     */
    public function provisionUser(string $username, string $password, Plan $plan): void
    {
        // 1. radcheck — Cleartext-Password
        Radcheck::create([
            'username'  => $username,
            'attribute' => 'Cleartext-Password',
            'op'        => ':=',
            'value'     => $password,
        ]);

        // 2. radreply — Mikrotik-Rate-Limit (download/upload dalam kbps)
        $rateLimit = "{$plan->download_speed_kbps}k/{$plan->upload_speed_kbps}k";
        Radreply::create([
            'username'  => $username,
            'attribute' => 'Mikrotik-Rate-Limit',
            'op'        => ':=',
            'value'     => $rateLimit,
        ]);

        // 3. radusergroup — mapping ke group paket
        Radusergroup::create([
            'username'  => $username,
            'groupname' => $plan->radius_group_name,
            'priority'  => 1,
        ]);
    }

    /**
     * Hapus semua entry RADIUS untuk user
     */
    public function deprovisionUser(string $username): void
    {
        Radcheck::where('username', $username)->delete();
        Radreply::where('username', $username)->delete();
        Radusergroup::where('username', $username)->delete();
    }

    /**
     * Isolir user — masukkan ke address list isolir
     */
    public function isolateUser(string $username): void
    {
        // Hapus Auth-Type = Reject jika sebelumnya ada (agar user tetap dapat IP)
        Radcheck::where('username', $username)
            ->where('attribute', 'Auth-Type')
            ->where('value', 'Reject')
            ->delete();

        // Tambahkan user ke Address List isolir di Mikrotik
        Radreply::firstOrCreate(
            ['username' => $username, 'attribute' => 'Mikrotik-Address-List'],
            ['op' => '=', 'value' => 'isolir']
        );
    }

    /**
     * Aktifkan user — hapus entry isolir
     */
    public function activateUser(string $username): void
    {
        // Hapus atribut Reject jika ada
        Radcheck::where('username', $username)
            ->where('attribute', 'Auth-Type')
            ->where('value', 'Reject')
            ->delete();

        // Hapus Address List isolir
        Radreply::where('username', $username)
            ->where('attribute', 'Mikrotik-Address-List')
            ->where('value', 'isolir')
            ->delete();
    }

    /**
     * Update speed limit di radreply
     */
    public function updateRateLimit(string $username, Plan $plan): void
    {
        $rateLimit = "{$plan->download_speed_kbps}k/{$plan->upload_speed_kbps}k";
        Radreply::where('username', $username)
            ->where('attribute', 'Mikrotik-Rate-Limit')
            ->update(['value' => $rateLimit]);
    }
}
