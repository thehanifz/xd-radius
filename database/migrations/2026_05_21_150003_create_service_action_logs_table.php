<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_action_logs')) {
            return;
        }

        Schema::create('service_action_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('action');
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamp('performed_at');
            $table->text('notes')->nullable();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_action_logs');
    }
};
