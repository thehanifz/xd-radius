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
            if (! Schema::hasColumn('payment_attempts', 'public_token_encrypted')) {
                $table->text('public_token_encrypted')->nullable()->after('public_token_hash');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_attempts')) {
            return;
        }

        Schema::table('payment_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('payment_attempts', 'public_token_encrypted')) {
                $table->dropColumn('public_token_encrypted');
            }
        });
    }
};
