<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Voucher;
use App\Models\Radcheck;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use App\Services\RadiusService;

class VoucherValidityService
{
    public function __construct(protected ?RadiusService $radius = null) {}

    public function expiryAt(Plan $plan, CarbonInterface $start): CarbonInterface
    {
        return match ($plan->duration_unit) {
            'minutes' => $start->copy()->addMinutes($plan->duration_value),
            'hours'   => $start->copy()->addHours($plan->duration_value),
            'days'    => $start->copy()->addDays($plan->duration_value),
            default   => throw new \InvalidArgumentException('Unit durasi voucher tidak valid.'),
        };
    }

    public function activateFromFirstLogin(Voucher $voucher, CarbonInterface $loginAt): Voucher
    {
        return DB::transaction(function () use ($voucher, $loginAt) {
            $voucher->refresh();

            if ($voucher->first_login_at !== null) {
                return $voucher;
            }

            $expiredAt = $this->expiryAt($voucher->plan, $loginAt);

            $voucher->update([
                'first_login_at' => $loginAt,
                'activated_at'   => $loginAt,
                'expired_at'     => $expiredAt,
                'status'         => 'active',
            ]);

            $remaining = max(0, $loginAt->diffInSeconds($expiredAt, false));

            // First login receives the full session duration from provisioning.
            // After the first accounting record is known, subsequent logins are
            // constrained by the fixed expiry timestamp.
            $radius = $this->radius ?? app(RadiusService::class);
            $radius->setExpiration($voucher->username, (string) $expiredAt->timestamp);
            $radius->setSessionTimeout($voucher->username, $remaining);
            Radcheck::where('username', $voucher->username)
                ->where('attribute', 'Auth-Type')
                ->where('value', 'Reject')
                ->delete();

            return $voucher->fresh();
        });
    }

    public function remainingSeconds(Voucher $voucher, ?CarbonInterface $now = null): ?int
    {
        if ($voucher->expired_at === null) {
            return null;
        }

        $seconds = ($now ?? now())->diffInSeconds($voucher->expired_at, false);
        return max(0, $seconds);
    }

    public function markExpired(): int
    {
        $expired = Voucher::query()
            ->where('status', 'active')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->get(['id', 'username']);

        foreach ($expired as $voucher) {
            $voucher->update(['status' => 'expired']);

            // Keep Expiration in radcheck so rlm_expiration continues to reject
            // the voucher on future authentication attempts.
            Radcheck::updateOrCreate(
                ['username' => $voucher->username, 'attribute' => 'Auth-Type'],
                ['op' => ':=', 'value' => 'Reject']
            );
        }

        return $expired->count();
    }
}
