<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('routers', 'last_connection_message')) {
            Schema::table('routers', function (Blueprint $table) {
                $table->text('last_connection_message')
                    ->nullable()
                    ->after('last_connection_error');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('routers', 'last_connection_message')) {
            Schema::table('routers', function (Blueprint $table) {
                $table->dropColumn('last_connection_message');
            });
        }
    }
};
