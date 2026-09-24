<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('doku_settings')) {
            return;
        }

        Schema::create('doku_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('environment', 32)->default('sandbox');
            $table->string('base_url', 255)->default('https://api-sandbox.doku.com');
            $table->string('client_id', 128)->nullable();
            $table->text('secret_key')->nullable();
            $table->text('api_key')->nullable();
            $table->string('merchant_id', 128)->nullable();
            $table->string('terminal_id', 128)->nullable();
            $table->string('channel_id', 64)->default('H2H');
            $table->string('va_bank', 32)->default('BNI');
            $table->string('va_partner_service_id', 64)->nullable();
            $table->string('va_customer_prefix', 16)->default('3');
            $table->string('notification_path', 255)->default('/webhooks/doku');
            $table->unsignedInteger('timeout')->default(15);
            $table->string('private_key_path', 255)->default('/etc/xd-radius/doku/private.key');
            $table->text('private_key_passphrase')->nullable();
            $table->string('public_key_path', 255)->default('/etc/xd-radius/doku/public.key');
            $table->string('key_fingerprint', 80)->nullable();
            $table->timestamps();
        });

        DB::table('doku_settings')->insert([
            'enabled' => filter_var(env('DOKU_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
            'environment' => env('DOKU_ENVIRONMENT', 'sandbox'),
            'base_url' => env('DOKU_BASE_URL', 'https://api-sandbox.doku.com'),
            'client_id' => env('DOKU_CLIENT_ID'),
            'secret_key' => filled(env('DOKU_SECRET_KEY')) ? Crypt::encryptString(env('DOKU_SECRET_KEY')) : null,
            'api_key' => filled(env('DOKU_API_KEY')) ? Crypt::encryptString(env('DOKU_API_KEY')) : null,
            'merchant_id' => env('DOKU_MERCHANT_ID'),
            'terminal_id' => env('DOKU_TERMINAL_ID'),
            'channel_id' => env('DOKU_CHANNEL_ID', 'H2H'),
            'va_bank' => env('DOKU_VA_BANK', 'BNI'),
            'va_partner_service_id' => env('DOKU_VA_PARTNER_SERVICE_ID'),
            'va_customer_prefix' => env('DOKU_VA_CUSTOMER_PREFIX', '3'),
            'notification_path' => env('DOKU_NOTIFICATION_PATH', '/webhooks/doku'),
            'timeout' => (int) env('DOKU_HTTP_TIMEOUT', 15),
            'private_key_path' => env('DOKU_PRIVATE_KEY_PATH', '/etc/xd-radius/doku/private.key'),
            'private_key_passphrase' => filled(env('DOKU_PRIVATE_KEY_PASSPHRASE')) ? Crypt::encryptString(env('DOKU_PRIVATE_KEY_PASSPHRASE')) : null,
            'public_key_path' => '/etc/xd-radius/doku/public.key',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('doku_settings');
    }
};
