<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Plan;
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

            $this->syncToRadius($member, $plan, $data['password']);

            return $member->load('plan');
        });
    }

    public function update(Member $member, array $data): Member
    {
        $plan = Plan::findOrFail($data['plan_id']);

        return DB::transaction(function () use ($member, $data, $plan) {
            $passwordChanged = isset($data['password']) && $data['password'] !== '';
            $newPassword     = $passwordChanged ? $data['password'] : null;

            $member->update([
                'plan_id'          => $plan->id,
                'price_snapshot'   => $data['price_snapshot'] ?? $member->price_snapshot,
                'simultaneous_use' => $data['simultaneous_use'] ?? $member->simultaneous_use,
                'status'           => $data['status'] ?? $member->status,
                'expired_at'       => $data['expired_at'] ?? $member->expired_at,
                'notes'            => $data['notes'] ?? null,
            ]);

            if ($passwordChanged) {
                $member->password_plain = $newPassword;
                $member->save();
            }

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
            $newStatus = $member->status === 'active' ? 'isolated' : 'active';

            if ($newStatus === 'isolated') {
                DB::table('radcheck')->updateOrInsert(
                    ['username' => $member->username, 'attribute' => 'Auth-Type'],
                    ['op' => ':=', 'value' => 'Reject']
                );
            } else {
                DB::table('radcheck')
                    ->where('username', $member->username)
                    ->where('attribute', 'Auth-Type')
                    ->delete();
            }

            $member->update(['status' => $newStatus]);
            return $member->fresh();
        });
    }

    private function syncToRadius(Member $member, Plan $plan, string $password): void
    {
        $u         = $member->username;
        $rateLimit = "{$plan->download_speed_kbps}k/{$plan->upload_speed_kbps}k";

        DB::table('radcheck')->insert([
            'username'  => $u,
            'attribute' => 'Cleartext-Password',
            'op'        => ':=',
            'value'     => $password,
        ]);

        DB::table('radreply')->insert([
            ['username' => $u, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $rateLimit],
            ['username' => $u, 'attribute' => 'Simultaneous-Use',   'op' => ':=', 'value' => (string) $member->simultaneous_use],
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

        if ($newPassword) {
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
        $val  = $plan->duration_value ?? 30;
        $unit = $plan->duration_unit  ?? 'days';

        return match ($unit) {
            'hours'  => $dt->modify("+{$val} hours"),
            'months' => $dt->modify("+{$val} months"),
            default  => $dt->modify("+{$val} days"),
        };
    }
}
