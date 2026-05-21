<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'upload_speed',
                'download_speed',
                'duration_value',
                'duration_unit',
                'simultaneous_use',
            ]);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->string('type', 20)->default('voucher')->after('name');
            $table->unsignedInteger('download_speed_kbps')->default(1024)->after('type');
            $table->unsignedInteger('upload_speed_kbps')->default(512)->after('download_speed_kbps');
            $table->unsignedInteger('duration_days')->default(30)->after('upload_speed_kbps');
            $table->unsignedBigInteger('data_quota_mb')->nullable()->after('duration_days');
            $table->string('radius_group_name', 100)->nullable()->after('data_quota_mb');
            $table->index('type');
        });

        DB::statement("UPDATE plans SET radius_group_name = LOWER(REPLACE(REPLACE(name, ' ', '-'), '/', '-')) || '-' || id::text");

        Schema::table('plans', function (Blueprint $table) {
            $table->string('radius_group_name', 100)->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        // Drop index jika ada
        DB::statement('DROP INDEX IF EXISTS plans_type_index');
        DB::statement('DROP INDEX IF EXISTS plans_radius_group_name_unique');

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'download_speed_kbps',
                'upload_speed_kbps',
                'duration_days',
                'data_quota_mb',
                'radius_group_name',
            ]);
            $table->integer('upload_speed')->default(512);
            $table->integer('download_speed')->default(1024);
            $table->integer('duration_value')->default(1);
            $table->string('duration_unit')->default('days');
            $table->smallInteger('simultaneous_use')->default(1);
        });
    }
};
