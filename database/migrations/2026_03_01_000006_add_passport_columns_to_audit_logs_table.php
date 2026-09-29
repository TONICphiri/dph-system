<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1: spec-shaped columns on audit_logs.
 * Legacy subject_type/subject_id/description/facility_id columns are kept
 * for backwards compatibility; new code writes entity_type/entity_id/details.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'entity_type')) {
                $table->string('entity_type', 100)->nullable()->after('action')->index();
            }

            if (! Schema::hasColumn('audit_logs', 'entity_id')) {
                $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type')->index();
            }

            if (! Schema::hasColumn('audit_logs', 'details')) {
                $table->json('details')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            foreach (['details', 'entity_id', 'entity_type'] as $column) {
                if (Schema::hasColumn('audit_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
