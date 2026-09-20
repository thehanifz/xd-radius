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
            '/usr/bin/psql', '/usr/bin/pg_dump', '/usr/bin/readlink', '/usr/bin/test', '/usr/bin/install', '/usr/bin/ln', '/usr/bin/rm', '/usr/bin/sudo', '/usr/local/sbin/xd-radius-freeradius',
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

        // PHP may return -1 from proc_close() when proc_get_status() has already
        // reaped the process. Keep the authoritative exit code captured above.
        $statusExit = isset($status['exitcode']) ? (int) $status['exitcode'] : -1;
        $closeExit = proc_close($process);
        $exit = ($statusExit >= 0) ? $statusExit : $closeExit;

        return ['exit_code' => $exit, 'stdout' => trim($stdout), 'stderr' => trim($stderr)];
    }

    public function execute(string $binary, array $arguments = [], int $timeout = 30, bool $privileged = false): array
    {
        $command = array_merge([$binary], $arguments);
        if ($privileged && filter_var(env('FREERADIUS_USE_SUDO', true), FILTER_VALIDATE_BOOL)) {
            $helper = env('FREERADIUS_PRIVILEGED_HELPER', '/usr/local/sbin/xd-radius-freeradius');
            $operation = $this->helperOperation($binary, $arguments);
            $command = array_merge(['/usr/bin/sudo', '-n', $helper], $operation);
        }
        return $this->run($command, $timeout);
    }
    private function helperOperation(string $binary, array $arguments): array
    {
        $base = basename($binary);
        if ($base === 'install') {
            if (count($arguments) !== 4 || $arguments[0] !== '-m' || $arguments[1] !== '0640') {
                throw new RuntimeException('Operasi install FreeRADIUS tidak diizinkan.');
            }
            return ['install', '0640', $arguments[2], $arguments[3]];
        }
        if ($base === 'ln') {
            if (count($arguments) !== 3 || $arguments[0] !== '-s') {
                throw new RuntimeException('Operasi symlink FreeRADIUS tidak diizinkan.');
            }
            return ['symlink', $arguments[1], $arguments[2]];
        }
        if ($base === 'rm') {
            if (count($arguments) !== 1) {
                throw new RuntimeException('Operasi remove FreeRADIUS tidak diizinkan.');
            }
            return ['remove', $arguments[0]];
        }
        if ($base === 'systemctl') {
            if (count($arguments) === 2 && in_array($arguments[0], ['reload', 'restart'], true) && $arguments[1] === 'freeradius') {
                return ['systemctl', $arguments[0], 'freeradius'];
            }
            if (count($arguments) === 2 && $arguments[0] === 'is-active' && $arguments[1] === 'freeradius') {
                return ['is-active', 'freeradius'];
            }
        }
        throw new RuntimeException('Command privileged FreeRADIUS tidak diizinkan.');
    }

}
