<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Radacct extends Model
{
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
        $diff = now()->diff($start);
        if ($diff->days > 0) return $diff->days . 'h ' . $diff->h . 'm';
        if ($diff->h > 0)    return $diff->h . 'j ' . $diff->i . 'm';
        return $diff->i . 'm ' . $diff->s . 'd';
    }
}
