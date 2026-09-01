<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Voucher;
use App\Models\Radcheck;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class VoucherValidityService
{
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

            // FreeRADIUS can enforce expiry at authentication time after the
            // first login has established the voucher's fixed expiry.
            Radcheck::updateOrCreate(
                ['username' => $voucher->username, 'attribute' => 'Expiration'],
                ['op' => ':=', 'value' => $expiredAt->format('M j Y H:i:s')]
            );

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
        return Voucher::query()
            ->where('status', 'active')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->update(['status' => 'expired']);
    }
}
