<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoucherService
{
    public function __construct(protected RadiusService $radius) {}

    /**
     * Generate voucher batch
     *
     * @param array $data {
     *   plan_id, prefix, length, charset_mode, quantity, notes, generated_by
     * }
     */
    public function generate(array $data): VoucherBatch
    {
        $plan = Plan::findOrFail($data['plan_id']);

        return DB::transaction(function () use ($data, $plan) {
            // Buat batch record
            $batch = VoucherBatch::create([
                'batch_code'   => $this->generateBatchCode(),
                'prefix'       => $data['prefix'] ?? null,
                'length'       => (int) $data['length'],
                'charset_mode' => $data['charset_mode'],
                'quantity'     => (int) $data['quantity'],
                'plan_id'      => $plan->id,
                'generated_by' => $data['generated_by'],
                'generated_at' => now(),
                'notes'        => $data['notes'] ?? null,
            ]);

            $vouchers  = [];
            $usedNames = [];
            $attempts  = 0;
            $maxAttempts = $data['quantity'] * 10;

            while (count($vouchers) < $data['quantity'] && $attempts < $maxAttempts) {
                $attempts++;
                $username = $this->generateUsername(
                    $data['prefix'] ?? '',
                    (int) $data['length'],
                    $data['charset_mode'],
                    $usedNames
                );

                if ($username === null) continue;

                $usedNames[] = $username;
                $now = now();

                $vouchers[] = [
                    'batch_id'       => $batch->id,
                    'username'       => $username,
                    'password_plain' => encrypt($username), // username = password
                    'plan_id'        => $plan->id,
                    'price_snapshot' => $plan->price,
                    'status'         => 'active',
                    'is_printed'     => false,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }

            // Bulk insert vouchers
            Voucher::insert($vouchers);

            // Provision ke RADIUS — bulk insert langsung untuk performa
            $radchecks   = [];
            $radreplies  = [];
            $radusergroups = [];
            $rateLimit = "{$plan->download_speed_kbps}k/{$plan->upload_speed_kbps}k";

            foreach ($vouchers as $v) {
                $username = $v['username'];

                $radchecks[] = [
                    'username'  => $username,
                    'attribute' => 'Cleartext-Password',
                    'op'        => ':=',
                    'value'     => $username, // plaintext untuk FreeRADIUS
                ];

                $radreplies[] = [
                    'username'  => $username,
                    'attribute' => 'Mikrotik-Rate-Limit',
                    'op'        => ':=',
                    'value'     => $rateLimit,
                ];

                $radusergroups[] = [
                    'username'  => $username,
                    'groupname' => $plan->radius_group_name,
                    'priority'  => 1,
                ];
            }

            DB::table('radcheck')->insert($radchecks);
            DB::table('radreply')->insert($radreplies);
            DB::table('radusergroup')->insert($radusergroups);

            return $batch->load('plan', 'generatedBy', 'vouchers');
        });
    }

    /**
     * Generate username unik
     */
    private function generateUsername(string $prefix, int $length, string $charsetMode, array $used): ?string
    {
        $chars = match ($charsetMode) {
            'numeric'      => '0123456789',
            'alpha_upper'  => 'ABCDEFGHJKLMNPQRSTUVWXYZ', // hapus I,O supaya tidak mirip 1,0
            'alpha_lower'  => 'abcdefghjkmnpqrstuvwxyz',  // hapus i,l,o
            'alpha'        => 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz',
            'alphanumeric' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789', // no ambiguous chars
            default        => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
        };

        $suffixLength = $length - strlen($prefix);
        if ($suffixLength <= 0) return null;

        $maxTries = 20;
        for ($i = 0; $i < $maxTries; $i++) {
            $suffix   = '';
            for ($j = 0; $j < $suffixLength; $j++) {
                $suffix .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $username = $prefix . $suffix;

            // Cek duplikasi di batch saat ini
            if (in_array($username, $used)) continue;

            // Cek duplikasi di database (vouchers + members)
            $exists = DB::table('vouchers')->where('username', $username)->exists()
                   || DB::table('members')->where('username', $username)->exists();

            if (!$exists) return $username;
        }

        return null;
    }

    private function generateBatchCode(): string
    {
        return 'BATCH-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }

    /**
     * Preview contoh username berdasarkan konfigurasi
     */
    public static function previewExample(string $prefix, int $length, string $charsetMode): string
    {
        $chars = match ($charsetMode) {
            'numeric'      => '0123456789',
            'alpha_upper'  => 'ABCDEFGHJKLMNPQRSTUVWXYZ',
            'alpha_lower'  => 'abcdefghjkmnpqrstuvwxyz',
            'alpha'        => 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz',
            'alphanumeric' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
            default        => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
        };

        $suffixLength = $length - strlen($prefix);
        if ($suffixLength <= 0) return $prefix;

        $suffix = '';
        for ($i = 0; $i < $suffixLength; $i++) {
            $suffix .= $chars[array_rand(str_split($chars))];
        }
        return $prefix . $suffix;
    }
}
