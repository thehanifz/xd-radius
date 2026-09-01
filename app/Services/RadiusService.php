<?php

namespace App\Services;

use App\Models\Radcheck;
use App\Models\Radreply;
use App\Models\Radusergroup;
use App\Models\Plan;

class RadiusService
{
    public function __construct(protected QosService $qos) {}

    public function provisionUser(string $username, string $password, Plan $plan): void
    {
        Radcheck::updateOrCreate(
            ['username' => $username, 'attribute' => 'Cleartext-Password'],
            ['op' => ':=', 'value' => $password]
        );

        Radreply::where('username', $username)
            ->whereIn('attribute', ['Mikrotik-Rate-Limit', 'Session-Timeout', 'Expiration', 'Simultaneous-Use'])
            ->delete();

        $this->upsertReply($username, 'Mikrotik-Rate-Limit', $this->qos->rateLimit($plan));
        $this->upsertReply($username, 'Simultaneous-Use', '1');

        Radusergroup::updateOrCreate(
            ['username' => $username],
            ['groupname' => $plan->radius_group_name, 'priority' => 1]
        );
    }

    public function setSessionTimeout(string $username, int $seconds): void
    {
        Radreply::updateOrCreate(
            ['username' => $username, 'attribute' => 'Session-Timeout'],
            ['op' => ':=', 'value' => (string) max(0, $seconds)]
        );
    }

    public function setExpiration(string $username, string $expiration): void
    {
        Radcheck::updateOrCreate(
            ['username' => $username, 'attribute' => 'Expiration'],
            ['op' => ':=', 'value' => $expiration]
        );
    }

    public function deprovisionUser(string $username): void
    {
        Radcheck::where('username', $username)->delete();
        Radreply::where('username', $username)->delete();
        Radusergroup::where('username', $username)->delete();
    }

    public function isolateUser(string $username): void
    {
        Radcheck::where('username', $username)
            ->where('attribute', 'Auth-Type')
            ->where('value', 'Reject')
            ->delete();

        Radreply::firstOrCreate(
            ['username' => $username, 'attribute' => 'Mikrotik-Address-List'],
            ['op' => '=', 'value' => 'isolir']
        );
    }

    public function activateUser(string $username): void
    {
        Radcheck::where('username', $username)
            ->where('attribute', 'Auth-Type')
            ->where('value', 'Reject')
            ->delete();

        Radreply::where('username', $username)
            ->where('attribute', 'Mikrotik-Address-List')
            ->where('value', 'isolir')
            ->delete();
    }

    public function updateRateLimit(string $username, Plan $plan): void
    {
        $this->upsertReply($username, 'Mikrotik-Rate-Limit', $this->qos->rateLimit($plan));
    }

    private function upsertReply(string $username, string $attribute, string $value): void
    {
        Radreply::updateOrCreate(
            ['username' => $username, 'attribute' => $attribute],
            ['op' => ':=', 'value' => $value]
        );
    }
}
