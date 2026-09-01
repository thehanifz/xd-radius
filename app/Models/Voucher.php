<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    protected $table = 'vouchers';

    protected $fillable = [
        'batch_id',
        'username',
        'password_plain',
        'plan_id',
        'price_snapshot',
        'status',
        'first_login_at',
        'activated_at',
        'expired_at',
        'is_printed',
    ];

    protected $casts = [
        'first_login_at'  => 'datetime',
        'activated_at'    => 'datetime',
        'expired_at'      => 'datetime',
        'is_printed'      => 'boolean',
        'price_snapshot'  => 'integer',
    ];

    protected $hidden = ['password_plain'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(VoucherBatch::class, 'batch_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active'   => $this->first_login_at ? 'Aktif' : 'Tersedia',
            'used'     => 'Digunakan',
            'expired'  => 'Expired',
            'isolated' => 'Isolir',
            'inactive' => 'Nonaktif',
            default    => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active'   => $this->first_login_at ? 'green' : 'blue',
            'used'     => 'blue',
            'expired'  => 'gray',
            'isolated' => 'red',
            'inactive' => 'yellow',
            default    => 'gray',
        };
    }

    public function getPriceLabelAttribute(): string
    {
        return 'Rp ' . number_format($this->price_snapshot, 0, ',', '.');
    }

    public function getRemainingSecondsAttribute(): ?int
    {
        if (!$this->expired_at) return null;
        return max(0, now()->diffInSeconds($this->expired_at, false));
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'active')->whereNull('first_login_at');
    }

    public function scopeUsed($query)
    {
        return $query->where('status', 'active')->whereNotNull('first_login_at');
    }

    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }
}
