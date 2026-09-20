<?php

namespace App\Services\Radius;

use Illuminate\Support\Facades\DB;
use Throwable;

class FreeRadiusHealthChecker
{
    public function __construct(private readonly FreeRadiusEnvironment $environment, private readonly FreeRadiusValidator $validator, private readonly FreeRadiusCommandRunner $runner) {}

    public function check(): array
    {
        $env = $this->environment->inspect();
        $checks = [];
        $checks['service'] = $env['service_active'];
        $validation = $this->validator->validate($env['binary']);
        $checks['configuration'] = $validation['ok'];
        $checks['database'] = $env['database']['ok'];
        $checks['sql_module'] = $this->sqlModuleUsable();
        $checks['tables'] = $this->requiredTablesExist();
        $checks['listeners'] = $this->listenersAvailable();

        return ['healthy' => ! in_array(false, $checks, true), 'checks' => $checks, 'validation' => $validation, 'environment' => $env];
    }

    private function sqlModuleUsable(): bool
    {
        try { DB::connection('radius')->table('radcheck')->limit(1)->get(); DB::connection('radius')->table('nas')->limit(1)->get(); return true; }
        catch (Throwable) { return false; }
    }

    private function requiredTablesExist(): bool
    {
        try {
            $schema = DB::connection('radius')->getSchemaBuilder();
            foreach (['radcheck', 'radreply', 'radacct', 'nas'] as $table) if (! $schema->hasTable($table)) return false;
            return true;
        } catch (Throwable) { return false; }
    }

    private function listenersAvailable(): bool
    {
        foreach ([1812, 1813] as $port) {
            $result = $this->runner->execute('/usr/bin/ss', ['-lntup']);
            $text = $result['stdout'];
            if (! str_contains($text, ':' . $port)) return false;
        }
        return true;
    }
}
