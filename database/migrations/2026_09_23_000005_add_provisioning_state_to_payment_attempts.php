<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_attempts')) {
            return;
        }

        Schema::table('payment_attempts', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_attempts', 'provisioning_status')) {
                $table->string('provisioning_status', 32)->default('pending')->after('status');
            }
            if (! Schema::hasColumn('payment_attempts', 'provisioning_error')) {
                $table->text('provisioning_error')->nullable()->after('failure_reason');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_attempts')) {
            return;
        }

        Schema::table('payment_attempts', function (Blueprint $table) {
            foreach (['provisioning_error', 'provisioning_status'] as $column) {
                if (Schema::hasColumn('payment_attempts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
