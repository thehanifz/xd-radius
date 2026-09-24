<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('doku_va_channels')) {
            return;
        }

        // These fields are retained for backward compatibility with v4/v5,
        // but new VA numbers no longer depend on a per-bank customer prefix.
        DB::statement('ALTER TABLE doku_va_channels ALTER COLUMN partner_service_id DROP NOT NULL');
        DB::statement('ALTER TABLE doku_va_channels ALTER COLUMN customer_prefix DROP NOT NULL');
        DB::statement('ALTER TABLE doku_va_channels ALTER COLUMN customer_prefix DROP DEFAULT');
    }

    public function down(): void
    {
        if (! Schema::hasTable('doku_va_channels')) {
            return;
        }

        DB::table('doku_va_channels')->whereNull('partner_service_id')->update(['partner_service_id' => '0']);
        DB::table('doku_va_channels')->whereNull('customer_prefix')->update(['customer_prefix' => '3']);
        DB::statement("ALTER TABLE doku_va_channels ALTER COLUMN partner_service_id SET NOT NULL");
        DB::statement("ALTER TABLE doku_va_channels ALTER COLUMN customer_prefix SET DEFAULT '3'");
        DB::statement('ALTER TABLE doku_va_channels ALTER COLUMN customer_prefix SET NOT NULL');
    }
};
