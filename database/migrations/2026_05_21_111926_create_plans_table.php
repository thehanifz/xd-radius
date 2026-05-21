<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('upload_speed');
            $table->unsignedInteger('download_speed');
            $table->unsignedInteger('duration_value');
            $table->enum('duration_unit', ['hours', 'days', 'months']);
            $table->unsignedBigInteger('price');
            $table->unsignedTinyInteger('simultaneous_use')->default(1);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
