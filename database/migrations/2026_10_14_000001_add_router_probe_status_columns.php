<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->string('last_ping_status')->nullable()->after('last_connection_status');
            $table->timestamp('last_ping_at')->nullable()->after('last_ping_status');
            $table->decimal('last_ping_latency_ms', 8, 2)->nullable()->after('last_ping_at');
            $table->text('last_ping_error')->nullable()->after('last_ping_latency_ms');
            $table->string('last_api_status')->nullable()->after('last_ping_error');
            $table->timestamp('last_api_at')->nullable()->after('last_api_status');
            $table->text('last_api_error')->nullable()->after('last_api_at');
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn([
                'last_ping_status',
                'last_ping_at',
                'last_ping_latency_ms',
                'last_ping_error',
                'last_api_status',
                'last_api_at',
                'last_api_error',
            ]);
        });
    }
};
