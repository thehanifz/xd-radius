<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Superseded by 2026_05_21_150003_create_service_action_logs_table.php
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_action_logs')) {
            return;
        }

        Schema::create('service_action_logs', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('action');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Handled by 150003
    }
};
