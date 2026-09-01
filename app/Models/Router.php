<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
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
        'location',
        'is_active',
        'last_connection_status',
        'last_connected_at',
        'last_connection_message',
        'routeros_version',
        'last_connection_error',
    ];

    protected $casts = [
        'api_secret'        => 'encrypted',
        'api_port'          => 'integer',
        'is_active'         => 'boolean',
        'last_connected_at' => 'datetime',
    ];

    // --- Activity Log ---

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'ip_address', 'api_port', 'api_username', 'location', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // --- Sync ke tabel nas FreeRADIUS ---

    /**
     * Upsert baris di tabel nas agar FreeRADIUS mengenali router ini sebagai RADIUS client.
     * Hanya dijalankan jika radius_secret diisi.
     */
    public function syncToNas(): void
    {
        if (! $this->radius_secret) return;

        DB::table('nas')->updateOrInsert(
            ['nasname' => $this->ip_address],
            [
                'shortname'   => $this->name,
                'type'        => 'other',
                'secret'      => $this->radius_secret,
                'description' => $this->location ?? $this->name,
                'server'      => null,
                'community'   => null,
                'ports'       => 0,
            ]
        );
    }

    /**
     * Hapus baris dari tabel nas saat router dihapus.
     */
    public function removeFromNas(): void
    {
        DB::table('nas')->where('nasname', $this->ip_address)->delete();
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
        return match ($this->last_connection_status) {
            'ok'    => 'Terhubung',
            'error' => 'Gagal',
            default => 'Belum diuji',
        };
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
        return match ($this->last_connection_status) {
            'ok'    => 'green',
            'error' => 'red',
            default => 'gray',
        };
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
