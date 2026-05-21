<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Superseded by 2026_05_21_150001_create_billing_invoices_table.php
// File ini dipertahankan agar migration history tidak putus, tapi skip jika tabel sudah ada.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('billing_invoices')) {
            return;
        }

        Schema::create('billing_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members');
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedBigInteger('amount');
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Handled by 150001
    }
};
