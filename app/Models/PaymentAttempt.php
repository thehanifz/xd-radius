<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    public ?string $public_token = null;
    protected $fillable = [
        'invoice_id', 'gateway', 'channel', 'provider_reference',
        'provider_transaction_id', 'amount', 'status', 'payment_url',
        'qr_content', 'public_token_hash', 'public_token_encrypted', 'public_token_expires_at',
        'expires_at', 'paid_at', 'failure_reason', 'provisioning_status', 'provisioning_error', 'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'public_token_expires_at' => 'datetime',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'invoice_id');
    }

    public function getAmountLabelAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
