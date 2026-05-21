<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('voucher_batches');
            $table->string('username')->unique();
            $table->text('password_plain');
            $table->foreignId('plan_id')->constrained('plans');
            $table->unsignedBigInteger('price_snapshot');
            $table->enum('status', ['active', 'isolated', 'expired', 'inactive'])->default('active');
            $table->timestamp('first_login_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->boolean('is_printed')->default(false);
            $table->timestamps();

            $table->index('username');
            $table->index('status');
            $table->index('batch_id');
            $table->index('expired_at');
            $table->index('first_login_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
