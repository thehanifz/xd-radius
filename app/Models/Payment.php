<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id', 'amount', 'paid_at',
        'payment_method', 'external_transaction_id',
        'gateway_status', 'notes',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'invoice_id');
    }

    public function getAmountLabelAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash'     => 'Tunai',
            'transfer' => 'Transfer',
            'qris'     => 'QRIS',
            default    => $this->payment_method,
        };
    }
}
