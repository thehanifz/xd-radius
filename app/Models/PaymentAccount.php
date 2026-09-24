<?php

namespace App\Models;

use App\Casts\EncryptedOrPlaintext;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAccount extends Model
{
    protected $fillable = [
        'member_id', 'gateway', 'bank', 'channel', 'doku_va_channel_id',
        'provider_customer_id', 'partner_service_id', 'provider_account_id', 'account_number',
        'status', 'is_default', 'metadata',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'partner_service_id' => EncryptedOrPlaintext::class,
        'metadata' => 'array',
    ];

    public function dokuVaChannel(): BelongsTo
    {
        return $this->belongsTo(DokuVaChannel::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function getAccountNumberLabelAttribute(): string
    {
        return preg_replace('/(?<=.{4}).(?=.{4})/', '*', $this->account_number);
    }
}
