<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Superseded by 2026_05_21_150002_create_payments_table.php
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            return;
        }

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('billing_invoices');
            $table->unsignedBigInteger('amount');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->default('cash');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Handled by 150002
    }
};
