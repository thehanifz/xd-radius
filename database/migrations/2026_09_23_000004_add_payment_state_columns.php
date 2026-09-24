<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'status')) {
                $table->string('status', 32)->default('paid')->after('amount');
            }
            if (! Schema::hasColumn('payments', 'gateway')) {
                $table->string('gateway', 32)->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('payments', 'provider_reference')) {
                $table->string('provider_reference', 128)->nullable()->after('external_transaction_id');
            }
            if (! Schema::hasColumn('payments', 'metadata')) {
                $table->json('metadata')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            foreach (['metadata', 'provider_reference', 'gateway', 'status'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
