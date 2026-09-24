<?php

namespace App\Models;

use App\Casts\EncryptedOrPlaintext;

use Illuminate\Database\Eloquent\Model;

class DokuSetting extends Model
{
    protected $fillable = [
        'enabled', 'environment', 'base_url', 'client_id', 'secret_key', 'api_key',
        'merchant_id', 'terminal_id', 'channel_id', 'va_bank', 'va_partner_service_id',
        'va_customer_prefix', 'notification_path', 'timeout', 'private_key_path',
        'private_key_passphrase', 'public_key_path', 'key_fingerprint',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'client_id' => EncryptedOrPlaintext::class,
        'secret_key' => 'encrypted',
        'api_key' => 'encrypted',
        'merchant_id' => EncryptedOrPlaintext::class,
        'terminal_id' => EncryptedOrPlaintext::class,
        'va_partner_service_id' => EncryptedOrPlaintext::class,
        'va_customer_prefix' => EncryptedOrPlaintext::class,
        'private_key_passphrase' => 'encrypted',
        'timeout' => 'integer',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'enabled' => false,
            'environment' => 'sandbox',
            'base_url' => 'https://api-sandbox.doku.com',
            'channel_id' => 'H2H',
            'va_bank' => 'BNI',
            'va_customer_prefix' => '3',
            'notification_path' => '/webhooks/doku',
            'timeout' => 15,
            'private_key_path' => '/etc/xd-radius/doku/private.key',
            'public_key_path' => '/etc/xd-radius/doku/public.key',
        ]);
    }

    public function toConfig(): array
    {
        return [
            'enabled' => $this->enabled,
            'environment' => $this->environment,
            'base_url' => $this->base_url,
            'client_id' => $this->client_id,
            'secret_key' => $this->secret_key,
            'api_key' => $this->api_key,
            'doku_public_key' => null,
            'private_key_path' => $this->private_key_path,
            'private_key_passphrase' => $this->private_key_passphrase,
            'merchant_id' => $this->merchant_id,
            'terminal_id' => $this->terminal_id,
            'channel_id' => $this->channel_id ?: 'H2H',
            'va_bank' => $this->va_bank ?: 'BNI',
            'va_partner_service_id' => $this->va_partner_service_id,
            'va_customer_prefix' => $this->va_customer_prefix ?: '3',
            'notification_path' => $this->notification_path ?: '/webhooks/doku',
            'timeout' => $this->timeout ?: 15,
            'public_key_path' => $this->public_key_path,
            'key_fingerprint' => $this->key_fingerprint,
        ];
    }
}
