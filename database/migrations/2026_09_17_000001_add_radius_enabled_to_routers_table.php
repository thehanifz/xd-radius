<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->boolean('radius_enabled')->default(false)->after('radius_secret');
            $table->index('radius_enabled');
        });

        // Preserve existing RADIUS configuration: routers with a secret stay enabled.
        DB::table('routers')
            ->whereNotNull('radius_secret')
            ->update(['radius_enabled' => true]);
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropIndex(['radius_enabled']);
            $table->dropColumn('radius_enabled');
        });
    }
};
