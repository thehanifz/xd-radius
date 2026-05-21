<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code')->unique();
            $table->string('prefix', 20)->nullable();
            $table->unsignedTinyInteger('length');
            $table->enum('charset_mode', ['uppercase', 'lowercase', 'numeric', 'mixed']);
            $table->unsignedInteger('quantity');
            $table->foreignId('plan_id')->constrained('plans');
            $table->foreignId('generated_by')->constrained('app_users');
            $table->timestamp('generated_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('batch_code');
            $table->index('plan_id');
            $table->index('generated_by');
            $table->index('generated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_batches');
    }
};
