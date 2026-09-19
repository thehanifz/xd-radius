<?php

namespace App\Jobs;

use App\Models\Radacct;
use App\Models\Router;
use App\Models\Voucher;
use App\Models\Radcheck;
use App\Services\RouterConnectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireVoucherJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function __construct(public int $voucherId) {}

    public function handle(RouterConnectionService $connectionService): void
    {
        $voucher = Voucher::find($this->voucherId);

        if (! $voucher || $voucher->expired_at === null || $voucher->first_login_at === null) {
            return;
        }

        // Protect against an early execution caused by clock/queue timing.
        if ($voucher->expired_at->isFuture()) {
            self::dispatch($voucher->id)->delay($voucher->expired_at);
            return;
        }

        // Make the database/RADIUS state immediately expired. This is safe to
        // repeat if the job is retried after a RouterOS API failure.
        if ($voucher->status !== 'expired') {
            $voucher->update(['status' => 'expired']);
        }

        Radcheck::updateOrCreate(
            ['username' => $voucher->username, 'attribute' => 'Auth-Type'],
            ['op' => ':=', 'value' => 'Reject']
        );

        $sessions = Radacct::query()
            ->where('username', $voucher->username)
            ->whereNull('acctstoptime')
            ->get(['radacctid', 'nasipaddress']);

        if ($sessions->isEmpty()) {
            return;
        }

        $errors = [];

        foreach ($sessions->pluck('nasipaddress')->filter()->unique() as $nasIp) {
            $router = Router::withoutTrashed()
                ->where('ip_address', $nasIp)
                ->first();

            if (! $router) {
                $errors[] = "Router {$nasIp} tidak ditemukan untuk voucher {$voucher->username}.";
                continue;
            }

            try {
                $connectionService->disconnectUser($router, $voucher->username);
            } catch (\Throwable $e) {
                $errors[] = "Gagal disconnect {$voucher->username} dari {$router->name} ({$nasIp}): {$e->getMessage()}";
            }
        }

        if ($errors !== []) {
            Log::warning('ExpireVoucherJob gagal memutus sebagian sesi voucher.', [
                'voucher_id' => $voucher->id,
                'username' => $voucher->username,
                'errors' => $errors,
            ]);

            throw new \RuntimeException(implode(' ', $errors));
        }
    }
}
