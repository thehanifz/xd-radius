<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (! Schema::hasColumn('plans', 'mikrotik_rate_limit')) {
                $table->string('mikrotik_rate_limit', 255)->nullable()->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'mikrotik_rate_limit')) {
                $table->dropColumn('mikrotik_rate_limit');
            }
        });
    }
};
