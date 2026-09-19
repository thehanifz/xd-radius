<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Voucher;
use App\Models\Radcheck;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use App\Services\RadiusService;
use App\Jobs\ExpireVoucherJob;

class VoucherValidityService
{
    public function __construct(protected ?RadiusService $radius = null) {}

    public function expiryAt(Plan $plan, CarbonInterface $start): CarbonInterface
    {
        if (! in_array($plan->duration_unit, ['minutes', 'hours', 'days'], true)) {
            throw new \InvalidArgumentException('Unit durasi voucher tidak valid.');
        }

        return $plan->addDurationTo($start);
    }

    public function activateFromFirstLogin(Voucher $voucher, CarbonInterface $loginAt): Voucher
    {
        $activated = false;

        $voucher = DB::transaction(function () use ($voucher, $loginAt, &$activated) {
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

            // Session-Timeout is calculated by FreeRADIUS on every Access-Request
            // from the stored Expiration timestamp. Keeping a static Session-Timeout
            // in radreply would become stale after logout/re-login.
            $radius = $this->radius ?? app(RadiusService::class);
            $radius->setExpiration(
                $voucher->username,
                $expiredAt->format('d M Y H:i:s')
            );
            Radcheck::where('username', $voucher->username)
                ->where('attribute', 'Auth-Type')
                ->where('value', 'Reject')
                ->delete();

            $activated = true;

            return $voucher->fresh();
        });

        // One delayed job per activation. The queue worker wakes the job at
        // expired_at; no per-minute expiry comparison is required.
        if ($activated && $voucher->expired_at !== null) {
            ExpireVoucherJob::dispatch($voucher->id)->delay($voucher->expired_at);
        }

        return $voucher;
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
