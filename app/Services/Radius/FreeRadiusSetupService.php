<?php

namespace App\Services\Radius;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class FreeRadiusSetupService
{
    public function __construct(private readonly FreeRadiusEnvironment $environment, private readonly FreeRadiusCommandRunner $runner, private readonly FreeRadiusConfigurationPipeline $pipeline, private readonly FreeRadiusAuditService $audit, private readonly RadiusDatabaseBootstrapper $databaseBootstrapper) {}

    public function run(): array
    {
        $env = $this->environment->inspect();
        if (! $env['installed']) return ['ok' => false, 'step' => 'environment', 'message' => 'FreeRADIUS belum terinstall. MVP tidak menginstall package OS otomatis.'];
        try { $this->databaseBootstrapper->ensureDatabase(); } catch (Throwable $e) { return ['ok' => false, 'step' => 'database', 'message' => $e->getMessage()]; }
        $env = $this->environment->inspect();
        if (! $env['database']['ok']) return ['ok' => false, 'step' => 'database', 'message' => 'RADIUS database tidak dapat diakses.'];

        try {
            $this->bootstrapSchema($env['config_dir']);
            $this->audit->record('MIGRATION', 'success');
        } catch (Throwable $e) {
            $this->audit->record('MIGRATION', 'failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'step' => 'schema', 'message' => $e->getMessage()];
        }

        $result = $this->pipeline->run('SETUP');
        return $result + ['step' => $result['ok'] ? 'ready' : 'configuration'];
    }

    private function bootstrapSchema(?string $configDir): void
    {
        if (! $configDir) throw new RuntimeException('Config directory FreeRADIUS tidak ditemukan.');
        $schema = $configDir . '/mods-config/sql/main/postgresql/schema.sql';
        if (! is_file($schema)) throw new RuntimeException('Vendor schema PostgreSQL FreeRADIUS tidak ditemukan: ' . $schema);
        $schemaBuilder = DB::connection('radius')->getSchemaBuilder();
        if (! $schemaBuilder->hasTable('radcheck')) {
            try {
                DB::connection('radius')->unprepared(file_get_contents($schema));
            } catch (Throwable $e) {
                throw new RuntimeException('Vendor schema bootstrap gagal: ' . $e->getMessage(), 0, $e);
            }
        }
        if (! $schemaBuilder->hasColumn('radacct', 'is_stale')) $schemaBuilder->table('radacct', fn($table) => $table->boolean('is_stale')->nullable()->default(false));
        if (! $schemaBuilder->hasColumn('radacct', 'stale_detected_at')) $schemaBuilder->table('radacct', fn($table) => $table->timestamp('stale_detected_at')->nullable());
        $db = DB::connection('radius');
        if (! $schemaBuilder->hasTable('xd_radius_vendor_schema')) {
            $schemaBuilder->create('xd_radius_vendor_schema', function ($table) {
                $table->string('version', 50)->primary();
                $table->timestamp('installed_at');
            });
            $db->table('xd_radius_vendor_schema')->insert(['version' => app(FreeRadiusEnvironment::class)->inspect()['version'] ?: 'unknown', 'installed_at' => now()]);
        }
        if (! $schemaBuilder->hasTable('xd_radius_extension_migrations')) {
            $schemaBuilder->create('xd_radius_extension_migrations', function ($table) {
                $table->string('migration', 150)->primary();
                $table->timestamp('applied_at');
            });
        }
        foreach (['radacct.is_stale', 'radacct.stale_detected_at'] as $extension) {
            $name = str_replace('.', '_', $extension);
            if (! $db->table('xd_radius_extension_migrations')->where('migration', $name)->exists()) {
                $db->table('xd_radius_extension_migrations')->insert(['migration' => $name, 'applied_at' => now()]);
            }
        }
    }
}
