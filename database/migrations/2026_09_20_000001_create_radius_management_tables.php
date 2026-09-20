<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('radius_management_operations', function (Blueprint $table) {
            $table->id();
            $table->uuid('operation_id')->unique();
            $table->string('type', 40);
            $table->string('status', 30)->index();
            $table->string('target_type', 80)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->text('message')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::table('routers', function (Blueprint $table) {
            $table->string('radius_sync_status', 20)->default('pending')->index();
            $table->timestamp('radius_last_synced_at')->nullable();
            $table->text('radius_last_sync_error')->nullable();
            $table->string('radius_applied_fingerprint', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn(['radius_sync_status', 'radius_last_synced_at', 'radius_last_sync_error', 'radius_applied_fingerprint']);
        });
        Schema::dropIfExists('radius_management_operations');
    }
};
