<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingInvoice extends Model
{
    protected $fillable = [
        'member_id', 'period_start', 'period_end',
        'amount', 'status', 'due_date', 'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'due_date'     => 'date',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }

    public function getAmountLabelAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'Menunggu',
            'paid'      => 'Lunas',
            'overdue'   => 'Jatuh Tempo',
            'cancelled' => 'Dibatalkan',
            default     => $this->status,
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'badge-yellow',
            'paid'      => 'badge-green',
            'overdue'   => 'badge-red',
            'cancelled' => 'badge-slate',
            default     => 'badge-slate',
        };
    }

    public function scopePending($q)  { return $q->where('status', 'pending'); }
    public function scopeOverdue($q)  { return $q->where('status', 'overdue'); }
    public function scopePaid($q)     { return $q->where('status', 'paid'); }
}
