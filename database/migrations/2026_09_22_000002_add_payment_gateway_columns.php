<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'external_transaction_id')) {
                $table->string('external_transaction_id')->nullable()->after('payment_method');
            }

            if (!Schema::hasColumn('payments', 'gateway_status')) {
                $table->string('gateway_status')->nullable()->after('external_transaction_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'gateway_status')) {
                $table->dropColumn('gateway_status');
            }

            if (Schema::hasColumn('payments', 'external_transaction_id')) {
                $table->dropColumn('external_transaction_id');
            }
        });
    }
};
