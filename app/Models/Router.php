<?php

namespace App\Models;

use App\Jobs\Radius\RunConfigurationJob;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Router extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'routers';

    protected $fillable = [
        'name',
        'ip_address',
        'api_port',
        'api_username',
        'api_secret',
        'radius_secret',
        'radius_enabled',
        'location',
        'is_active',
        'last_connection_status',
        'last_ping_status',
        'last_ping_at',
        'last_ping_latency_ms',
        'last_ping_error',
        'last_api_status',
        'last_api_at',
        'last_api_error',
        'last_connected_at',
        'last_connection_message',
        'routeros_version',
        'last_connection_error',
        'radius_sync_status',
        'radius_last_synced_at',
        'radius_last_sync_error',
        'radius_applied_fingerprint',
    ];

    protected $casts = [
        'api_secret'        => 'encrypted',
        'api_port'          => 'integer',
        'is_active'         => 'boolean',
        'radius_enabled'    => 'boolean',
        'last_connected_at' => 'datetime',
        'last_ping_at' => 'datetime',
        'last_ping_latency_ms' => 'decimal:2',
        'last_api_at' => 'datetime',
        'radius_last_synced_at' => 'datetime',
    ];

    // --- Activity Log ---

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'ip_address', 'api_port', 'api_username', 'location', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // NAS synchronization is handled by the FreeRADIUS management pipeline.
    // Kept as compatibility methods for callers outside the current application.
    public function syncToNas(): void
    {
        RunConfigurationJob::dispatch('NAS_SYNC');
    }

    public function removeFromNas(): void
    {
        RunConfigurationJob::dispatch('NAS_SYNC');
    }

    // --- Accessors ---

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->is_active ? 'green' : 'gray';
    }

    public function getConnectionStatusLabelAttribute(): string
    {
        if (! $this->last_ping_at) return 'Belum diuji';
        if ($this->last_ping_status === 'error') return 'Tidak terhubung';
        if (! $this->ping_is_fresh) return 'Data usang';

        return $this->last_ping_status === 'ok' ? 'Terhubung' : 'Belum diuji';
    }

    public function getPingIsFreshAttribute(): bool
    {
        return $this->last_ping_at?->greaterThanOrEqualTo(now()->subMinutes(3)) ?? false;
    }

    public function getConnectionHealthyAttribute(): bool
    {
        return $this->ping_is_fresh && $this->last_ping_status === 'ok';
    }

    public function getPingStatusLabelAttribute(): string
    {
        if (! $this->last_ping_at) return 'Belum diuji';
        if (! $this->ping_is_fresh) return 'Data usang';

        return $this->last_ping_status === 'ok' ? 'Terjangkau' : 'Gagal';
    }

    public function getApiStatusLabelAttribute(): string
    {
        if (! $this->last_api_at) return 'Belum diuji';

        return $this->last_api_status === 'ok' ? 'Berfungsi' : 'Gagal';
    }

    public function getConnectionResourceAttribute(): array
    {
        $value = $this->last_connection_message;
        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function getConnectionCpuLoadAttribute(): ?int
    {
        return isset($this->connection_resource['cpu_load']) ? (int) $this->connection_resource['cpu_load'] : null;
    }

    public function getConnectionUptimeAttribute(): ?string
    {
        return $this->connection_resource['uptime'] ?? null;
    }

    public function getConnectionMemoryLabelAttribute(): ?string
    {
        $free = $this->connection_resource['free_memory'] ?? null;
        $total = $this->connection_resource['total_memory'] ?? null;
        if (! $free || ! $total) {
            return null;
        }

        return number_format($free / 1048576, 0) . ' / ' . number_format($total / 1048576, 0) . ' MB';
    }

    public function getConnectionStatusColorAttribute(): string
    {
        if ($this->connection_healthy) return 'green';
        if ($this->last_ping_status === 'error') return 'red';
        if ($this->last_ping_at) return 'amber';

        return 'gray';
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeConnectionHealthy($query)
    {
        $freshAfter = now()->subMinutes(3);

        return $query->where('last_ping_status', 'ok')
            ->where('last_ping_at', '>=', $freshAfter);
    }
}
