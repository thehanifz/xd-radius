<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Plan;
use App\Models\ServiceActionLog;
use Illuminate\Support\Facades\DB;

class MemberService
{
    public function create(array $data): Member
    {
        $plan = Plan::findOrFail($data['plan_id']);

        return DB::transaction(function () use ($data, $plan) {
            $activatedAt = now();
            $expiredAt   = $this->calcExpiry($activatedAt, $plan);

            $member = Member::create([
                'username'         => $data['username'],
                'password_plain'   => $data['password'],
                'plan_id'          => $plan->id,
                'price_snapshot'   => $plan->price,
                'simultaneous_use' => $data['simultaneous_use'] ?? 1,
                'status'           => 'active',
                'activated_at'     => $activatedAt,
                'expired_at'       => $expiredAt,
                'notes'            => $data['notes'] ?? null,
            ]);

            // Sync ke FreeRADIUS — gunakan password dari request (plaintext),
            // BUKAN dari $member->password_plain karena sudah ter-encrypt di model
            $this->syncToRadius($member, $plan, $data['password']);

            return $member->load('plan');
        });
    }

    public function update(Member $member, array $data): Member
    {
        $plan = Plan::findOrFail($data['plan_id']);

        return DB::transaction(function () use ($member, $data, $plan) {
            $newPassword     = (isset($data['password']) && $data['password'] !== '') ? $data['password'] : null;
            $passwordChanged = $newPassword !== null;

            $updateData = [
                'plan_id'          => $plan->id,
                'price_snapshot'   => $data['price_snapshot'] ?? $member->price_snapshot,
                'simultaneous_use' => $data['simultaneous_use'] ?? $member->simultaneous_use,
                'status'           => $data['status'] ?? $member->status,
                'expired_at'       => $data['expired_at'] ?? $member->expired_at,
                'notes'            => $data['notes'] ?? null,
            ];

            // Update password_plain (encrypted) hanya jika ada password baru
            if ($passwordChanged) {
                $updateData['password_plain'] = $newPassword;
            }

            $member->update($updateData);

            // Update RADIUS — jika password berubah, kirim plaintext baru
            $this->updateRadius($member, $plan, $newPassword);

            return $member->fresh('plan');
        });
    }

    public function delete(Member $member): void
    {
        DB::transaction(function () use ($member) {
            $u = $member->username;
            DB::table('radcheck')->where('username', $u)->delete();
            DB::table('radreply')->where('username', $u)->delete();
            DB::table('radusergroup')->where('username', $u)->delete();
            $member->delete();
        });
    }

    public function toggleStatus(Member $member): Member
    {
        return DB::transaction(function () use ($member) {
            $prevStatus = $member->status;
            $newStatus  = $prevStatus === 'active' ? 'isolated' : 'active';

            if ($newStatus === 'isolated') {
                // Tambah Auth-Type := Reject ke radcheck
                DB::table('radcheck')->updateOrInsert(
                    [
                        'username'  => $member->username,
                        'attribute' => 'Auth-Type',
                    ],
                    [
                        'op'    => ':=',
                        'value' => 'Reject',
                    ]
                );
            } else {
                // Hapus entry Reject dari radcheck
                DB::table('radcheck')
                    ->where('username', $member->username)
                    ->where('attribute', 'Auth-Type')
                    ->where('value', 'Reject')
                    ->delete();
            }

            $member->update(['status' => $newStatus]);

            // Catat di service_action_logs
            ServiceActionLog::record(
                'member',
                $member->id,
                $newStatus === 'isolated' ? 'isolate' : 'activate',
                $prevStatus,
                $newStatus,
                auth('app')->id()
            );

            return $member->fresh();
        });
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function syncToRadius(Member $member, Plan $plan, string $password): void
    {
        $u         = $member->username;
        $rateLimit = "{$plan->download_speed_kbps}k/{$plan->upload_speed_kbps}k";

        DB::table('radcheck')->insert([
            'username'  => $u,
            'attribute' => 'Cleartext-Password',
            'op'        => ':=',
            'value'     => $password,  // plaintext dari request, sebelum encrypt
        ]);

        DB::table('radreply')->insert([
            [
                'username'  => $u,
                'attribute' => 'Mikrotik-Rate-Limit',
                'op'        => ':=',
                'value'     => $rateLimit,
            ],
            [
                'username'  => $u,
                'attribute' => 'Simultaneous-Use',
                'op'        => ':=',
                'value'     => (string) $member->simultaneous_use,
            ],
        ]);

        DB::table('radusergroup')->insert([
            'username'  => $u,
            'groupname' => $plan->radius_group_name,
            'priority'  => 1,
        ]);
    }

    private function updateRadius(Member $member, Plan $plan, ?string $newPassword): void
    {
        $u         = $member->username;
        $rateLimit = "{$plan->download_speed_kbps}k/{$plan->upload_speed_kbps}k";

        if ($newPassword !== null) {
            DB::table('radcheck')
                ->where('username', $u)
                ->where('attribute', 'Cleartext-Password')
                ->update(['value' => $newPassword]);
        }

        DB::table('radreply')
            ->where('username', $u)
            ->where('attribute', 'Mikrotik-Rate-Limit')
            ->update(['value' => $rateLimit]);

        DB::table('radreply')
            ->where('username', $u)
            ->where('attribute', 'Simultaneous-Use')
            ->update(['value' => (string) $member->simultaneous_use]);

        DB::table('radusergroup')
            ->where('username', $u)
            ->update(['groupname' => $plan->radius_group_name]);
    }

    private function calcExpiry(\DateTime $from, Plan $plan): \DateTime
    {
        $dt   = clone $from;
        $days = $plan->duration_days ?? 30;
        return $dt->modify("+{$days} days");
    }
}
