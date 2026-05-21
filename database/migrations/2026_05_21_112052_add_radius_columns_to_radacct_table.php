<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('radacct', function (Blueprint $table) {
            $table->boolean('is_stale')->default(false)->after('acctstoptime');
            $table->timestamp('stale_detected_at')->nullable()->after('is_stale');

            $table->index('is_stale');
            $table->index('stale_detected_at');
        });
    }

    public function down(): void
    {
        Schema::table('radacct', function (Blueprint $table) {
            $table->dropColumn(['is_stale', 'stale_detected_at']);
        });
    }
};
