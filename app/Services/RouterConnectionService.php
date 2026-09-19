<?php

namespace App\Services;

use App\Models\Router;
use RouterOS\Client;
use RouterOS\Query;

class RouterConnectionService
{
    /**
     * Disconnect all active RouterOS sessions for a RADIUS username.
     *
     * The same RADIUS username may be connected through HotSpot or PPP/PPPoE.
     * We intentionally check both RouterOS session tables instead of relying
     * on NAS-Port-Type alone; radacct remains the accounting source of truth.
     * The NAS sends Accounting-Stop after RouterOS removes the session.
     */
    public function disconnectUser(Router $router, string $username): int
    {
        $client = new Client([
            'host' => $router->ip_address,
            'user' => $router->api_username,
            'pass' => $router->api_secret,
            'port' => $router->api_port,
            'timeout' => 5,
        ]);

        $removed = 0;

        // HotSpot sessions.
        $hotspotSessions = $client->query(
            (new Query('/ip/hotspot/active/print'))->where('user', $username)
        )->read();

        foreach ($hotspotSessions as $session) {
            if (empty($session['.id'])) {
                continue;
            }

            $client->query(
                (new Query('/ip/hotspot/active/remove'))->equal('.id', $session['.id'])
            )->read();

            $removed++;
        }

        // PPP/PPPoE sessions.
        $pppSessions = $client->query(
            (new Query('/ppp/active/print'))->where('name', $username)
        )->read();

        foreach ($pppSessions as $session) {
            if (empty($session['.id'])) {
                continue;
            }

            $client->query(
                (new Query('/ppp/active/remove'))->equal('.id', $session['.id'])
            )->read();

            $removed++;
        }

        return $removed;
    }

    public function test(Router $router): array
    {
        try {
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->api_username,
                'pass' => $router->api_secret,
                'port' => $router->api_port,
                'timeout' => 5,
            ]);

            $response = $client->query('/system/resource/print')->read();
            $resource = $response[0] ?? [];

            $router->forceFill([
                'last_connection_status' => 'ok',
                'last_connected_at' => now(),
                'last_connection_error' => null,
                'routeros_version' => $resource['version'] ?? $router->routeros_version,
                'last_connection_message' => json_encode([
                    'board' => $resource['board-name'] ?? null,
                    'uptime' => $resource['uptime'] ?? null,
                    'cpu_load' => isset($resource['cpu-load']) ? (int) $resource['cpu-load'] : null,
                    'free_memory' => isset($resource['free-memory']) ? (int) $resource['free-memory'] : null,
                    'total_memory' => isset($resource['total-memory']) ? (int) $resource['total-memory'] : null,
                ]),
            ])->save();

            return [
                'ok' => true,
                'version' => $resource['version'] ?? null,
                'board' => $resource['board-name'] ?? null,
                'uptime' => $resource['uptime'] ?? null,
                'cpu_load' => isset($resource['cpu-load']) ? (int) $resource['cpu-load'] : null,
                'free_memory' => isset($resource['free-memory']) ? (int) $resource['free-memory'] : null,
                'total_memory' => isset($resource['total-memory']) ? (int) $resource['total-memory'] : null,
            ];
        } catch (\Throwable $e) {
            $router->forceFill([
                'last_connection_status' => 'error',
                'last_connection_error' => $e->getMessage(),
            ])->save();

            return [
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
