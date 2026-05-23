<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // -- Umum --
            [
                'key'         => 'app_name',
                'value'       => 'RadiusManager',
                'type'        => 'string',
                'group'       => 'general',
                'label'       => 'Nama Aplikasi',
                'description' => 'Digunakan pada title browser, header, dan nama file export.',
            ],
            [
                'key'         => 'ssid_name',
                'value'       => 'WiFi Saya',
                'type'        => 'string',
                'group'       => 'general',
                'label'       => 'Nama SSID / Jaringan',
                'description' => 'Nama jaringan yang dicetak pada kartu voucher.',
            ],

            // -- Rekonsiliasi Sesi --
            [
                'key'         => 'stale_threshold_minutes',
                'value'       => '30',
                'type'        => 'integer',
                'group'       => 'radius',
                'label'       => 'Threshold Sesi Stale (menit)',
                'description' => 'Sesi yang tidak diperbarui melebihi menit ini akan ditandai Diduga Putus. Rekomendasi: 2x interval Interim-Update NAS (default 30 menit).',
            ],

            // -- Billing --
            [
                'key'         => 'invoice_days_before',
                'value'       => '7',
                'type'        => 'integer',
                'group'       => 'billing',
                'label'       => 'Hari Generate Invoice Sebelum Jatuh Tempo',
                'description' => 'Invoice otomatis dibuat H-N sebelum masa aktif member berakhir.',
            ],
            [
                'key'         => 'overdue_isolate_auto',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'billing',
                'label'       => 'Auto Isolir Member Overdue',
                'description' => 'Jika aktif, member dengan tagihan overdue akan diisolir otomatis oleh Scheduler setiap hari jam 02:00.',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
