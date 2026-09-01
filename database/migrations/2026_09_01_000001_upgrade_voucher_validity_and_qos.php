<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (! Schema::hasColumn('plans', 'duration_value')) {
                $table->unsignedInteger('duration_value')->nullable()->after('upload_speed_kbps');
            }
            if (! Schema::hasColumn('plans', 'duration_unit')) {
                $table->string('duration_unit', 12)->default('days')->after('duration_value');
            }
            if (! Schema::hasColumn('plans', 'qos_limit_at_down_kbps')) {
                $table->unsignedInteger('qos_limit_at_down_kbps')->nullable()->after('data_quota_mb');
                $table->unsignedInteger('qos_limit_at_up_kbps')->nullable()->after('qos_limit_at_down_kbps');
                $table->unsignedInteger('qos_burst_limit_down_kbps')->nullable()->after('qos_limit_at_up_kbps');
                $table->unsignedInteger('qos_burst_limit_up_kbps')->nullable()->after('qos_burst_limit_down_kbps');
                $table->unsignedInteger('qos_burst_threshold_down_kbps')->nullable()->after('qos_burst_limit_up_kbps');
                $table->unsignedInteger('qos_burst_threshold_up_kbps')->nullable()->after('qos_burst_threshold_down_kbps');
                $table->unsignedInteger('qos_burst_time_down_sec')->nullable()->after('qos_burst_threshold_up_kbps');
                $table->unsignedInteger('qos_burst_time_up_sec')->nullable()->after('qos_burst_time_down_sec');
                $table->unsignedTinyInteger('qos_priority')->nullable()->after('qos_burst_time_up_sec');
                $table->string('qos_queue_type', 100)->nullable()->after('qos_priority');
            }
        });

        // Preserve the existing member-plan duration while giving voucher plans
        // a proper value/unit representation.
        \DB::statement("UPDATE plans SET duration_value = COALESCE(duration_days, 30), duration_unit = 'days' WHERE duration_value IS NULL");

        Schema::table('vouchers', function (Blueprint $table) {
            if (! Schema::hasColumn('vouchers', 'activated_at')) {
                $table->timestamp('activated_at')->nullable()->after('first_login_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('vouchers', 'activated_at')) {
                $table->dropColumn('activated_at');
            }
        });

        Schema::table('plans', function (Blueprint $table) {
            foreach ([
                'duration_value', 'duration_unit',
                'qos_limit_at_down_kbps', 'qos_limit_at_up_kbps',
                'qos_burst_limit_down_kbps', 'qos_burst_limit_up_kbps',
                'qos_burst_threshold_down_kbps', 'qos_burst_threshold_up_kbps',
                'qos_burst_time_down_sec', 'qos_burst_time_up_sec',
                'qos_priority', 'qos_queue_type',
            ] as $column) {
                if (Schema::hasColumn('plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
