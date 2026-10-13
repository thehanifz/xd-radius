<?php

namespace App\Jobs;

use App\Models\Member;
use App\Models\Radacct;
use App\Models\Radcheck;
use App\Models\Router;
use App\Services\RouterConnectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireMemberJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function __construct(public int $memberId) {}

    public function handle(RouterConnectionService $connectionService): void
    {
        $member = Member::find($this->memberId);

        if (! $member || $member->expired_at === null) {
            return;
        }

        if ($member->expired_at->isFuture()) {
            self::dispatch($member->id)->delay($member->expired_at);
            return;
        }

        if ($member->status !== 'expired') {
            $member->update(['status' => 'expired']);
        }

        Radcheck::updateOrCreate(
            ['username' => $member->username, 'attribute' => 'Auth-Type'],
            ['op' => ':=', 'value' => 'Reject']
        );

        // radacct may already be empty while RouterOS still has a remembered
        // HotSpot cookie. Clean every configured router, not only NASes present
        // in active accounting rows.
        $routers = Router::withoutTrashed()->get();
        $errors = [];

        foreach ($routers as $router) {
            try {
                $connectionService->disconnectUser($router, $member->username);
            } catch (\Throwable $e) {
                $errors[] = "Gagal membersihkan sesi/cookie {$member->username} dari {$router->name} ({$router->ip_address}): {$e->getMessage()}";
            }
        }

        if ($errors !== []) {
            Log::warning('ExpireMemberJob gagal membersihkan sesi/cookie member pada sebagian router.', [
                'member_id' => $member->id,
                'username' => $member->username,
                'errors' => $errors,
            ]);

            throw new \RuntimeException(implode(' ', $errors));
        }
    }
}