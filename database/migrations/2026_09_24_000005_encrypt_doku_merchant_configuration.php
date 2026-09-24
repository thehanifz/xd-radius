<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doku_settings', function (Blueprint $table) {
            $table->text('client_id')->nullable()->change();
            $table->text('merchant_id')->nullable()->change();
            $table->text('terminal_id')->nullable()->change();
            $table->text('va_partner_service_id')->nullable()->change();
            $table->text('va_customer_prefix')->nullable()->change();
        });

        Schema::table('doku_va_channels', function (Blueprint $table) {
            $table->text('merchant_bin')->nullable()->after('channel');
            $table->text('partner_service_id')->nullable()->change();
            $table->text('customer_prefix')->nullable()->change();
        });

        Schema::table('payment_accounts', function (Blueprint $table) {
            $table->text('partner_service_id')->nullable()->change();
        });

        $this->encryptColumns('doku_settings', [
            'client_id', 'merchant_id', 'terminal_id', 'va_partner_service_id', 'va_customer_prefix',
        ]);
        $this->encryptColumns('doku_va_channels', [
            'partner_service_id', 'customer_prefix', 'merchant_bin',
        ]);
        $this->encryptColumns('payment_accounts', ['partner_service_id']);
    }

    private function encryptColumns(string $table, array $columns): void
    {
        $query = DB::table($table)->select(array_merge(['id'], $columns));
        $query->orderBy('id')->chunkById(100, function ($rows) use ($table, $columns) {
            foreach ($rows as $row) {
                $updates = [];
                foreach ($columns as $column) {
                    $value = $row->{$column} ?? null;
                    if ($value !== null && $value !== '') {
                        $updates[$column] = Crypt::encryptString((string) $value);
                    }
                }
                if ($updates) {
                    $updates['updated_at'] = now();
                    DB::table($table)->where('id', $row->id)->update($updates);
                }
            }
        });
    }

    public function down(): void
    {
        $this->decryptColumns('doku_settings', [
            'client_id', 'merchant_id', 'terminal_id', 'va_partner_service_id', 'va_customer_prefix',
        ]);
        $this->decryptColumns('doku_va_channels', [
            'partner_service_id', 'customer_prefix', 'merchant_bin',
        ]);
        $this->decryptColumns('payment_accounts', ['partner_service_id']);

        Schema::table('doku_va_channels', function (Blueprint $table) {
            $table->dropColumn('merchant_bin');
            $table->string('partner_service_id', 64)->nullable()->change();
            $table->string('customer_prefix', 16)->nullable()->change();
        });

        Schema::table('payment_accounts', function (Blueprint $table) {
            $table->string('partner_service_id', 64)->nullable()->change();
        });

        Schema::table('doku_settings', function (Blueprint $table) {
            $table->string('client_id', 128)->nullable()->change();
            $table->string('merchant_id', 128)->nullable()->change();
            $table->string('terminal_id', 128)->nullable()->change();
            $table->string('va_partner_service_id', 64)->nullable()->change();
            $table->string('va_customer_prefix', 16)->default('3')->change();
        });
    }

    private function decryptColumns(string $table, array $columns): void
    {
        $query = DB::table($table)->select(array_merge(['id'], $columns));
        $query->orderBy('id')->chunkById(100, function ($rows) use ($table, $columns) {
            foreach ($rows as $row) {
                $updates = [];
                foreach ($columns as $column) {
                    $value = $row->{$column} ?? null;
                    if ($value !== null && $value !== '') {
                        try {
                            $updates[$column] = Crypt::decryptString((string) $value);
                        } catch (\Throwable) {
                            $updates[$column] = $value;
                        }
                    }
                }
                if ($updates) {
                    $updates['updated_at'] = now();
                    DB::table($table)->where('id', $row->id)->update($updates);
                }
            }
        });
    }
};
