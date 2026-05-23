<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'app_name',           'value' => 'RadiusManager',  'type' => 'string',  'group' => 'general', 'label' => 'Nama Aplikasi',          'description' => 'Nama yang ditampilkan di title dan header'],
            ['key' => 'ssid_name',          'value' => 'WiFi Hotspot',   'type' => 'string',  'group' => 'general', 'label' => 'Nama SSID / Jaringan',   'description' => 'Nama jaringan yang dicetak di voucher'],
            // Stale session
            ['key' => 'stale_threshold_minutes', 'value' => '30',        'type' => 'integer', 'group' => 'radius',  'label' => 'Threshold Sesi Stale (menit)', 'description' => 'Sesi dianggap stale jika tidak ada update selama X menit (default: 2× interval Interim-Update NAS)'],
            // Billing
            ['key' => 'invoice_days_before', 'value' => '7',             'type' => 'integer', 'group' => 'billing', 'label' => 'Generate Invoice (hari sebelum jatuh tempo)', 'description' => 'Invoice dibuat otomatis H-X sebelum jatuh tempo'],
            ['key' => 'overdue_isolate_auto', 'value' => '0',            'type' => 'boolean', 'group' => 'billing', 'label' => 'Auto Isolir Tagihan Overdue', 'description' => 'Isolir otomatis member yang tagihannya overdue'],
        ];

        foreach ($settings as $s) {
            DB::table('system_settings')->updateOrInsert(['key' => $s['key']], array_merge($s, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
