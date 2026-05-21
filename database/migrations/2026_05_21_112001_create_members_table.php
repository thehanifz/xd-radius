<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->text('password_plain');
            $table->foreignId('plan_id')->constrained('plans');
            $table->unsignedBigInteger('price_snapshot');
            $table->unsignedTinyInteger('simultaneous_use')->default(1);
            $table->enum('status', ['active', 'isolated', 'expired', 'inactive'])->default('active');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('first_login_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('username');
            $table->index('status');
            $table->index('expired_at');
            $table->index('first_login_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
