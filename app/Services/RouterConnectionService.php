<?php

namespace App\Services;

use App\Models\Router;
use RouterOS\Client;

class RouterConnectionService
{
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
