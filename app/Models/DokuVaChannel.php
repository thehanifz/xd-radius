<?php

namespace App\Models;

use App\Casts\EncryptedOrPlaintext;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DokuVaChannel extends Model
{
    protected $fillable = [
        'gateway', 'code', 'name', 'channel', 'merchant_bin', 'partner_service_id',
        'customer_prefix', 'enabled', 'is_default', 'metadata',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'is_default' => 'boolean',
        'merchant_bin' => EncryptedOrPlaintext::class,
        'partner_service_id' => EncryptedOrPlaintext::class,
        'customer_prefix' => EncryptedOrPlaintext::class,
        'metadata' => 'array',
    ];

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeForDoku($query)
    {
        return $query->where('gateway', 'doku');
    }

    public function paymentAccounts(): HasMany
    {
        return $this->hasMany(PaymentAccount::class, 'doku_va_channel_id');
    }

    public static function defaultDoku(): ?self
    {
        return static::forDoku()->enabled()->orderByDesc('is_default')->orderBy('name')->first();
    }
}
