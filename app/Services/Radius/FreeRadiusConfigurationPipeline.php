<?php

namespace App\Services\Radius;

use Illuminate\Support\Facades\Log;
use Throwable;

class FreeRadiusConfigurationPipeline
{
    public function __construct(
        private readonly FreeRadiusOperationLock $lock,
        private readonly FreeRadiusBackupManager $backup,
        private readonly FreeRadiusConfigGenerator $generator,
        private readonly FreeRadiusEnvironment $environment,
        private readonly FreeRadiusValidator $validator,
        private readonly FreeRadiusHealthChecker $health,
        private readonly FreeRadiusCommandRunner $runner,
        private readonly FreeRadiusAuditService $audit,
        private readonly FreeRadiusNasManager $nas,
    ) {}

    public function run(string $operation = 'CONFIG_UPDATE'): array
    {
        $lock = $this->lock->acquire((int) env('FREERADIUS_OPERATION_LOCK_TTL', 300));
        $backup = null;
        $nasSnapshot = [];
        try {
            $this->audit->record($operation, 'pending');
            $env = $this->environment->inspect();
            $backup = $this->backup->backup($env['config_dir']);
            $this->audit->record('BACKUP', 'success', ['backup' => $backup]);
            $nasSnapshot = $this->nas->snapshot();
            $generated = $this->generator->generate();
            $this->audit->record('CONFIG_UPDATE', 'generated', ['files' => $generated['files']]);

            $validation = $this->validator->validate($env['binary']);
            $this->audit->record('VALIDATION', $validation['ok'] ? 'success' : 'failed', ['output' => $validation['output']]);
            if (! $validation['ok']) {
                if ($backup) {
                    $this->backup->restore($backup, $env['config_dir']);
                    $this->audit->record('ROLLBACK', 'success');
                }
                return $this->failed($operation, $validation['output']);
            }

            $this->nas->apply();
            $this->audit->record('NAS_SYNC', 'success');

            $reload = $this->reloadOrRestart($env['config_dir']);
            $this->audit->record($reload['operation'], $reload['ok'] ? 'success' : 'failed');
            if (! $reload['ok']) {
                if ($backup) {
                    $this->backup->restore($backup, $env['config_dir']);
                    $this->nas->restore($nasSnapshot);
                    $this->audit->record('ROLLBACK', 'success');
                    $this->restart($env['binary']);
                }
                return $this->failed($operation, 'Reload/restart FreeRADIUS gagal.');
            }

            $health = $this->health->check();
            $this->audit->record('APPLY', $health['healthy'] ? 'success' : 'failed', ['checks' => $health['checks']]);
            if (! $health['healthy']) {
                if ($backup) {
                    $this->backup->restore($backup, $env['config_dir']);
                    $this->nas->restore($nasSnapshot);
                    $this->audit->record('ROLLBACK', 'success');
                    $this->restart($env['binary']);
                }
                return $this->failed($operation, 'Health check FreeRADIUS gagal.');
            }

            $this->audit->record($operation, 'success');
            return ['ok' => true, 'status' => 'in_sync', 'health' => $health, 'backup' => $backup];
        } catch (Throwable $e) {
            if ($backup) {
                try {
                    $env = $this->environment->inspect();
                    if (! empty($env['config_dir'])) $this->backup->restore($backup, $env['config_dir']);
                    if ($nasSnapshot !== []) $this->nas->restore($nasSnapshot);
                } catch (Throwable $rollbackError) {
                    Log::error('FreeRADIUS rollback failed', ['error' => $rollbackError->getMessage()]);
                }
            }
            Log::error('FreeRADIUS pipeline failed', ['operation' => $operation, 'error' => $e->getMessage()]);
            $this->audit->record($operation, 'failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'status' => 'failed', 'message' => $e->getMessage()];
        } finally {
            optional($lock)->release();
        }
    }

    private function reloadOrRestart(?string $configDir): array
    {
        $result = $this->runner->execute('/bin/systemctl', ['reload', 'freeradius'], 30, true);
        if ($result['exit_code'] !== 0) $result = $this->runner->execute('/usr/bin/systemctl', ['reload', 'freeradius'], 30, true);
        if ($result['exit_code'] === 0) return ['ok' => true, 'operation' => 'RELOAD'];
        return $this->restart(app(FreeRadiusEnvironment::class)->binary());
    }

    private function restart(?string $binary): array
    {
        try {
            $result = $this->runner->execute('/bin/systemctl', ['restart', 'freeradius'], 60, true);
            if ($result['exit_code'] !== 0) $result = $this->runner->execute('/usr/bin/systemctl', ['restart', 'freeradius'], 60, true);
            return ['ok' => $result['exit_code'] === 0, 'operation' => 'RESTART'];
        } catch (Throwable) { return ['ok' => false, 'operation' => 'RESTART']; }
    }

    private function failed(string $operation, string $message): array
    {
        $this->audit->record($operation, 'failed', ['message' => $message]);
        return ['ok' => false, 'status' => 'failed', 'message' => $message];
    }
}
