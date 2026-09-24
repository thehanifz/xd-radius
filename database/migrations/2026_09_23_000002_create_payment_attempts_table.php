<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_attempts')) {
            return;
        }

        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('billing_invoices')->cascadeOnDelete();
            $table->string('gateway', 32);
            $table->string('channel', 64);
            $table->string('provider_reference', 128)->nullable();
            $table->string('provider_transaction_id', 128)->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('status', 32)->default('pending');
            $table->text('payment_url')->nullable();
            $table->text('qr_content')->nullable();
            $table->string('public_token_hash', 64)->nullable()->unique();
            $table->timestamp('public_token_expires_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
            $table->index(['gateway', 'channel', 'status']);
            $table->index('provider_transaction_id');
            $table->index('provider_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
