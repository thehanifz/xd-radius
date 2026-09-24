<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_accounts')) {
            return;
        }

        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('gateway', 32);
            $table->string('bank', 32);
            $table->string('channel', 64)->nullable();
            $table->string('provider_customer_id', 64)->nullable();
            $table->string('provider_account_id', 128)->nullable();
            $table->string('account_number', 64);
            $table->string('status', 32)->default('active');
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'bank', 'account_number']);
            $table->index(['member_id', 'status']);
            $table->index(['member_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_accounts');
    }
};
