<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            // Shared secret untuk FreeRADIUS (terpisah dari API secret MikroTik)
            $table->string('radius_secret')->nullable()->after('api_secret');
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn('radius_secret');
        });
    }
};
