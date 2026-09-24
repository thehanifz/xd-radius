<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAccount extends Model
{
    protected $fillable = [
        'member_id', 'gateway', 'bank', 'channel',
        'provider_customer_id', 'provider_account_id', 'account_number',
        'status', 'is_default', 'metadata',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'metadata' => 'array',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function getAccountNumberLabelAttribute(): string
    {
        return preg_replace('/(?<=.{4}).(?=.{4})/', '*', $this->account_number);
    }
}
