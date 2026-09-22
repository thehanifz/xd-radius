<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_action_logs')) {
            return;
        }

        Schema::table('service_action_logs', function (Blueprint $table) {
            if (Schema::hasColumn('service_action_logs', 'subject_type')) {
                $table->renameColumn('subject_type', 'entity_type');
            }
            if (Schema::hasColumn('service_action_logs', 'subject_id')) {
                $table->renameColumn('subject_id', 'entity_id');
            }
            if (Schema::hasColumn('service_action_logs', 'from_status')) {
                $table->renameColumn('from_status', 'previous_status');
            }
            if (Schema::hasColumn('service_action_logs', 'to_status')) {
                $table->renameColumn('to_status', 'new_status');
            }
        });

        Schema::table('service_action_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('service_action_logs', 'performed_at')) {
                $table->timestamp('performed_at')->nullable();
            }
            if (! Schema::hasColumn('service_action_logs', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        // Preserve any existing history if this migration is ever applied to a
        // non-empty legacy table. The current production table is empty.
        if (Schema::hasColumn('service_action_logs', 'performed_at')) {
            \DB::table('service_action_logs')
                ->whereNull('performed_at')
                ->update(['performed_at' => \DB::raw('COALESCE(created_at, updated_at, CURRENT_TIMESTAMP)')]);
        }

        // The migration can be partially applied if PostgreSQL DDL survives a
        // failed migration run. IF NOT EXISTS makes retrying safe.
        \DB::statement('CREATE INDEX IF NOT EXISTS service_action_logs_entity_type_entity_id_index ON service_action_logs (entity_type, entity_id)');
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_action_logs')) {
            return;
        }

        \DB::statement('DROP INDEX IF EXISTS service_action_logs_entity_type_entity_id_index');

        Schema::table('service_action_logs', function (Blueprint $table) {
            if (Schema::hasColumn('service_action_logs', 'entity_type')) {
                $table->renameColumn('entity_type', 'subject_type');
            }
            if (Schema::hasColumn('service_action_logs', 'entity_id')) {
                $table->renameColumn('entity_id', 'subject_id');
            }
            if (Schema::hasColumn('service_action_logs', 'previous_status')) {
                $table->renameColumn('previous_status', 'from_status');
            }
            if (Schema::hasColumn('service_action_logs', 'new_status')) {
                $table->renameColumn('new_status', 'to_status');
            }
            if (Schema::hasColumn('service_action_logs', 'performed_at')) {
                $table->dropColumn('performed_at');
            }
            if (Schema::hasColumn('service_action_logs', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
