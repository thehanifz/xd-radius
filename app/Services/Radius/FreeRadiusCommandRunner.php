<?php

namespace App\Services\Radius;

use RuntimeException;

class FreeRadiusCommandRunner
{
    public function __construct(private readonly string $binary = '') {}

    public function run(array $command, int $timeout = 30): array
    {
        if ($command === []) {
            throw new RuntimeException('Command kosong.');
        }

        $allowed = [
            '/usr/sbin/freeradius', '/usr/sbin/radiusd', '/usr/bin/freeradius', '/usr/bin/radiusd',
            '/bin/systemctl', '/usr/bin/systemctl', '/usr/bin/ss', '/bin/ss',
            '/usr/bin/psql', '/usr/bin/pg_dump', '/usr/bin/readlink', '/usr/bin/test', '/usr/bin/install', '/usr/bin/ln', '/usr/bin/sudo',
        ];

        if (! in_array($command[0], $allowed, true) && ! ($command[0] === '/usr/bin/sudo' && isset($command[2]) && in_array($command[2], $allowed, true))) {
            throw new RuntimeException('Command tidak diizinkan oleh FreeRADIUS command runner.');
        }

        $escaped = array_map('escapeshellarg', $command);
        $process = proc_open(implode(' ', $escaped), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (! is_resource($process)) {
            throw new RuntimeException('Tidak dapat menjalankan command FreeRADIUS.');
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $started = microtime(true);
        $stdout = $stderr = '';

        do {
            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);
            $status = proc_get_status($process);
            if (! $status['running']) break;
            usleep(50_000);
        } while (microtime(true) - $started < $timeout);

        if (($status['running'] ?? false) === true) {
            proc_terminate($process);
            foreach ($pipes as $pipe) fclose($pipe);
            proc_close($process);
            throw new RuntimeException('Command FreeRADIUS timeout.');
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        return ['exit_code' => $exit, 'stdout' => trim($stdout), 'stderr' => trim($stderr)];
    }

    public function execute(string $binary, array $arguments = [], int $timeout = 30, bool $privileged = false): array
    {
        $command = array_merge([$binary], $arguments);
        if ($privileged && filter_var(env('FREERADIUS_USE_SUDO', false), FILTER_VALIDATE_BOOL)) {
            $command = array_merge(['/usr/bin/sudo', '-n'], $command);
        }
        return $this->run($command, $timeout);
    }
}
