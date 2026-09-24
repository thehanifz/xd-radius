<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_accounts', 'doku_va_channel_id')) {
                $table->foreignId('doku_va_channel_id')->nullable()->after('member_id')->constrained('doku_va_channels')->nullOnDelete();
            }
            if (! Schema::hasColumn('payment_accounts', 'partner_service_id')) {
                $table->string('partner_service_id', 64)->nullable()->after('provider_customer_id');
            }
        });

        if (Schema::hasColumn('payment_accounts', 'doku_va_channel_id') && Schema::hasTable('doku_va_channels')) {
            DB::statement("UPDATE payment_accounts pa SET doku_va_channel_id = c.id, partner_service_id = c.partner_service_id FROM doku_va_channels c WHERE pa.gateway = 'doku' AND UPPER(pa.bank) = UPPER(c.code)");
        }
    }

    public function down(): void
    {
        Schema::table('payment_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('payment_accounts', 'doku_va_channel_id')) {
                $table->dropForeign(['doku_va_channel_id']);
                $table->dropColumn('doku_va_channel_id');
            }
            if (Schema::hasColumn('payment_accounts', 'partner_service_id')) {
                $table->dropColumn('partner_service_id');
            }
        });
    }
};
