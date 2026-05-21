<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('billing_invoices')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->timestamp('paid_at');
            $table->string('payment_method')->default('cash'); // cash, transfer, etc.
            $table->string('external_transaction_id')->nullable(); // foundation payment gateway
            $table->string('gateway_status')->nullable();          // foundation payment gateway
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
