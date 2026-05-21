<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Member extends Model
{
    use SoftDeletes;

    protected $table = 'members';

    protected $fillable = [
        'username',
        'password_plain',
        'plan_id',
        'price_snapshot',
        'simultaneous_use',
        'status',
        'activated_at',
        'expired_at',
        'first_login_at',
        'notes',
    ];

    protected $casts = [
        'password_plain'   => 'encrypted',
        'activated_at'     => 'datetime',
        'expired_at'       => 'datetime',
        'first_login_at'   => 'datetime',
        'price_snapshot'   => 'integer',
        'simultaneous_use' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active'   => 'green',
            'isolated' => 'red',
            'expired'  => 'gray',
            'inactive' => 'yellow',
            default    => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active'   => 'Aktif',
            'isolated' => 'Isolir',
            'expired'  => 'Expired',
            'inactive' => 'Nonaktif',
            default    => ucfirst($this->status),
        };
    }

    public function getPriceSnapshotLabelAttribute(): string
    {
        return 'Rp ' . number_format($this->price_snapshot, 0, ',', '.');
    }
}
