<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('radacct')) {
            Schema::table('radacct', function (Blueprint $table) {
                if (! Schema::hasColumn('radacct', 'is_stale')) {
                    $table->boolean('is_stale')->default(false)->after('acctstoptime');
                }
                if (! Schema::hasColumn('radacct', 'stale_detected_at')) {
                    $table->timestamp('stale_detected_at')->nullable()->after('is_stale');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('radacct')) {
            Schema::table('radacct', function (Blueprint $table) {
                $table->dropColumnIfExists('is_stale');
                $table->dropColumnIfExists('stale_detected_at');
            });
        }
    }
};
