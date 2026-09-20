<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Radacct extends Model
{
    protected $connection = 'radius';
    protected $table      = 'radacct';
    protected $primaryKey = 'radacctid';
    public    $timestamps = false;

    protected $fillable = [
        'is_stale',
        'stale_detected_at',
    ];

    protected $casts = [
        'is_stale'         => 'boolean',
        'acctstarttime'    => 'datetime',
        'acctstoptime'     => 'datetime',
        'acctupdatetime'   => 'datetime',
        'stale_detected_at'=> 'datetime',
    ];

    // -------------------------------------------------------
    // Scopes
    // -------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->whereNull('acctstoptime')
                     ->where(fn($q) => $q->where('is_stale', false)->orWhereNull('is_stale'));
    }

    public function scopeStale($query)
    {
        return $query->whereNull('acctstoptime')->where('is_stale', true);
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    public function getDurationAttribute(): string
    {
        $start = $this->acctstarttime;
        if (! $start) return '-';

        $end = $this->acctstoptime ?: now();
        $seconds = max(0, $start->diffInSeconds($end));

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        if ($days > 0) return $days . 'h ' . $hours . 'j';
        if ($hours > 0) return $hours . 'j ' . $minutes . 'm';
        if ($minutes > 0) return $minutes . 'm ' . $secs . 'd';
        return $secs . 'd';
    }

    public function getUploadBytesAttribute(): int
    {
        return (int) ($this->acctoutputoctets ?? 0);
    }

    public function getDownloadBytesAttribute(): int
    {
        return (int) ($this->acctinputoctets ?? 0);
    }

    public function getTotalBytesAttribute(): int
    {
        return $this->upload_bytes + $this->download_bytes;
    }

    public function getUploadLabelAttribute(): string
    {
        return self::formatBytes($this->upload_bytes);
    }

    public function getDownloadLabelAttribute(): string
    {
        return self::formatBytes($this->download_bytes);
    }

    public function getTotalTrafficLabelAttribute(): string
    {
        return self::formatBytes($this->total_bytes);
    }

    public static function formatDuration(int|float|null $seconds): string
    {
        $seconds = max(0, (int) ($seconds ?? 0));
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($days > 0) return $days . 'h ' . $hours . 'j';
        if ($hours > 0) return $hours . 'j ' . $minutes . 'm';
        return $minutes . 'm';
    }

    public static function formatBytes(int|float $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = min((int) floor(log($bytes, 1024)), count($units) - 1);
        return round($bytes / (1024 ** $i), $i === 0 ? 0 : 2) . ' ' . $units[$i];
    }
}
