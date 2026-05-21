<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_action_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('action');
            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();
            $table->foreignId('performed_by')->constrained('app_users');
            $table->timestamp('performed_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('performed_by');
            $table->index('performed_at');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_action_logs');
    }
};
