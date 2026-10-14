<?php

namespace App\Services;

use App\Models\Router;
use Symfony\Component\Process\Process;
use RouterOS\Client;
use RouterOS\Query;

class RouterConnectionService
{
    /**
     * Return usernames with an active HotSpot or PPP session on a router.
     */
    public function activeUsernames(Router $router): array
    {
        $client = new Client([
            'host' => $router->ip_address,
            'user' => $router->api_username,
            'pass' => $router->api_secret,
            'port' => $router->api_port,
            'timeout' => 5,
        ]);

        $hotspotSessions = $client->query('/ip/hotspot/active/print')->read();
        $pppSessions = $client->query('/ppp/active/print')->read();
        $usernames = [];

        foreach (array_merge($hotspotSessions, $pppSessions) as $session) {
            $username = $session['user'] ?? $session['name'] ?? null;
            if (is_string($username) && $username !== '') {
                $usernames[] = $username;
            }
        }

        return array_values(array_unique($usernames));
    }

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

        // Remembered HotSpot cookies allow automatic re-login even after the
        // active session is removed. Remove only cookies matching this user.
        $hotspotCookies = $client->query(
            (new Query('/ip/hotspot/cookie/print'))->where('user', $username)
        )->read();

        foreach ($hotspotCookies as $cookie) {
            if (empty($cookie['.id'])) {
                continue;
            }

            $client->query(
                (new Query('/ip/hotspot/cookie/remove'))->equal('.id', $cookie['.id'])
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
        $testedAt = now();

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
                'last_api_status' => 'ok',
                'last_api_at' => $testedAt,
                'last_api_error' => null,
                'routeros_version' => $resource['version'] ?? $router->routeros_version,
                'last_connection_message' => json_encode([
                    'board' => $resource['board-name'] ?? null,
                    'uptime' => $resource['uptime'] ?? null,
                    'cpu_load' => isset($resource['cpu-load']) ? (int) $resource['cpu-load'] : null,
                    'free_memory' => isset($resource['free-memory']) ? (int) $resource['free-memory'] : null,
                    'total_memory' => isset($resource['total-memory']) ? (int) $resource['total-memory'] : null,
                ]),
            ])->save();
            $this->updateAggregateStatus($router);

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
                'last_api_status' => 'error',
                'last_api_at' => $testedAt,
                'last_api_error' => $e->getMessage(),
            ])->save();
            $this->updateAggregateStatus($router);

            return [
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function ping(Router $router): array
    {
        $testedAt = now();

        try {
            $process = new Process(['ping', '-n', '-c', '2', '-W', '2', $router->ip_address]);
            $process->setTimeout(6);
            $process->run();

            $output = trim($process->getOutput() . "\n" . $process->getErrorOutput());
            $ok = $process->isSuccessful();
            $latency = null;

            if ($ok && preg_match('/=\s*[\d.]+\/([\d.]+)\/[\d.]+/', $output, $match)) {
                $latency = (float) $match[1];
            } elseif ($ok && preg_match_all('/time[=<]([\d.]+)\s*ms/i', $output, $matches) && count($matches[1]) > 0) {
                $latency = array_sum(array_map('floatval', $matches[1])) / count($matches[1]);
            }

            $error = $ok ? null : ($output !== '' ? mb_substr($output, 0, 2000) : 'Router tidak membalas ping.');
            $router->forceFill([
                'last_ping_status' => $ok ? 'ok' : 'error',
                'last_ping_at' => $testedAt,
                'last_ping_latency_ms' => $latency,
                'last_ping_error' => $error,
            ])->save();
            $this->updateAggregateStatus($router);

            return [
                'ok' => $ok,
                'latency_ms' => $latency,
                'message' => $error,
            ];
        } catch (\Throwable $e) {
            $router->forceFill([
                'last_ping_status' => 'error',
                'last_ping_at' => $testedAt,
                'last_ping_latency_ms' => null,
                'last_ping_error' => $e->getMessage(),
            ])->save();
            $this->updateAggregateStatus($router);

            return ['ok' => false, 'latency_ms' => null, 'message' => $e->getMessage()];
        }
    }

    private function updateAggregateStatus(Router $router): void
    {
        $router->refresh();
        $pingFresh = $router->last_ping_at?->greaterThanOrEqualTo(now()->subMinutes(3)) ?? false;
        $healthy = $pingFresh && $router->last_ping_status === 'ok';
        $failed = $pingFresh && $router->last_ping_status !== 'ok';

        $router->forceFill([
            'last_connection_status' => $healthy ? 'ok' : ($failed ? 'error' : null),
            'last_connection_error' => $router->last_ping_error,
            'last_connected_at' => $healthy ? now() : $router->last_connected_at,
        ])->save();
    }
}
