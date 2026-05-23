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
    ];

    protected $casts = [
        'api_secret'        => 'encrypted',   // AES-256-CBC via APP_KEY
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
     * Dipanggil setelah store / update.
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
