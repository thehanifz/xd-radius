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

    public function generate(array $data): VoucherBatch
    {
        $plan = Plan::findOrFail($data['plan_id']);

        return DB::transaction(function () use ($data, $plan) {
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

            $vouchers    = [];
            $usedNames   = [];
            $attempts    = 0;
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
                    'password_plain' => $username,
                    'plan_id'        => $plan->id,
                    'price_snapshot' => $plan->price,
                    'status'         => 'active',
                    'is_printed'     => false,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }

            Voucher::insert($vouchers);

            $rateLimit     = "{$plan->download_speed_kbps}k/{$plan->upload_speed_kbps}k";
            $radchecks     = [];
            $radreplies    = [];
            $radusergroups = [];

            foreach ($vouchers as $v) {
                $u = $v['username'];
                $radchecks[]     = ['username' => $u, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => $u];
                $radreplies[]    = ['username' => $u, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $rateLimit];
                $radusergroups[] = ['username' => $u, 'groupname' => $plan->radius_group_name, 'priority' => 1];
            }

            DB::table('radcheck')->insert($radchecks);
            DB::table('radreply')->insert($radreplies);
            DB::table('radusergroup')->insert($radusergroups);

            return $batch->load('plan', 'generatedBy', 'vouchers');
        });
    }

    private function generateUsername(string $prefix, int $length, string $charsetMode, array $used): ?string
    {
        // Nilai sesuai DB constraint: uppercase, lowercase, numeric, mixed
        $chars = match ($charsetMode) {
            'numeric'   => '0123456789',
            'uppercase' => 'ABCDEFGHJKLMNPQRSTUVWXYZ',
            'lowercase' => 'abcdefghjkmnpqrstuvwxyz',
            'mixed'     => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
            default     => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
        };

        $suffixLength = $length - strlen($prefix);
        if ($suffixLength <= 0) return null;

        for ($i = 0; $i < 20; $i++) {
            $suffix = '';
            for ($j = 0; $j < $suffixLength; $j++) {
                $suffix .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $username = $prefix . $suffix;

            if (in_array($username, $used)) continue;

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

    public static function previewExample(string $prefix, int $length, string $charsetMode): string
    {
        $chars = match ($charsetMode) {
            'numeric'   => '0123456789',
            'uppercase' => 'ABCDEFGHJKLMNPQRSTUVWXYZ',
            'lowercase' => 'abcdefghjkmnpqrstuvwxyz',
            'mixed'     => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
            default     => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
        };

        $suffixLength = $length - strlen($prefix);
        if ($suffixLength <= 0) return $prefix;

        $charArr = str_split($chars);
        $suffix  = '';
        for ($i = 0; $i < $suffixLength; $i++) {
            $suffix .= $charArr[array_rand($charArr)];
        }
        return $prefix . $suffix;
    }
}
