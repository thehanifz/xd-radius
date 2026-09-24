<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('doku_va_channels')) {
            return;
        }

        Schema::create('doku_va_channels', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 32)->default('doku');
            $table->string('code', 32);
            $table->string('name', 80);
            $table->string('channel', 64);
            $table->string('partner_service_id', 64);
            $table->string('customer_prefix', 16)->default('3');
            $table->boolean('enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'code']);
            $table->index(['gateway', 'enabled']);
        });

        $setting = DB::table('doku_settings')->first();
        if ($setting && filled($setting->va_partner_service_id)) {
            DB::table('doku_va_channels')->insert([
                'gateway' => 'doku',
                'code' => strtoupper((string) ($setting->va_bank ?: 'BNI')),
                'name' => strtoupper((string) ($setting->va_bank ?: 'BNI')),
                'channel' => 'VIRTUAL_ACCOUNT_' . strtoupper((string) ($setting->va_bank ?: 'BNI')),
                'partner_service_id' => $setting->va_partner_service_id,
                'customer_prefix' => $setting->va_customer_prefix ?: '3',
                'enabled' => true,
                'is_default' => true,
                'metadata' => json_encode(['migrated_from_doku_settings' => true]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('doku_va_channels');
    }
};
