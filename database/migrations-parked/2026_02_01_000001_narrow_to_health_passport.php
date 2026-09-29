<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Narrow the system back to the Digital Health Passport scope.
 *
 * Upgrades live databases that already ran the pre-narrowing migrations:
 * - Drops the out-of-scope hospital-management tables (wards/beds, visits/
 *   vitals, medicines/prescriptions, admissions and related tables).
 * - Renames doctor_schedules / doctor_id to health_worker_schedules /
 *   health_worker_id.
 *
 * Fresh installs do not need this migration: the out-of-scope migration
 * files were deleted and the scheduling migration already uses the new
 * names, so every step below is guarded and skips when there is nothing
 * to do.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Foreign keys added to existing tables by the admissions migration.
        foreach (['vitals', 'prescriptions'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'admission_id')) {
                continue;
            }

            try {
                Schema::table($table, fn (Blueprint $table) => $table->dropForeign(['admission_id']));
            } catch (Throwable) {
                // Constraint was never created; nothing to drop.
            }
        }

        // Dependants first, then the tables they point to.
        Schema::dropIfExists('medication_administrations');
        Schema::dropIfExists('progress_notes');
        Schema::dropIfExists('admissions');
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('medicines');
        Schema::dropIfExists('vitals');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('beds');
        Schema::dropIfExists('wards');

        if (Schema::hasTable('doctor_schedules') && ! Schema::hasTable('health_worker_schedules')) {
            Schema::rename('doctor_schedules', 'health_worker_schedules');
        }

        foreach (['health_worker_schedules', 'appointments', 'appointment_reviews'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (Schema::hasColumn($table, 'doctor_id') && ! Schema::hasColumn($table, 'health_worker_id')) {
                Schema::table($table, fn (Blueprint $table) => $table->renameColumn('doctor_id', 'health_worker_id'));
            }
        }
    }

    public function down(): void
    {
        // Reverses the renames only. The dropped hospital-management tables
        // are not restored; their definitions were removed from the codebase.
        foreach (['health_worker_schedules', 'appointments', 'appointment_reviews'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (Schema::hasColumn($table, 'health_worker_id') && ! Schema::hasColumn($table, 'doctor_id')) {
                Schema::table($table, fn (Blueprint $table) => $table->renameColumn('health_worker_id', 'doctor_id'));
            }
        }

        if (Schema::hasTable('health_worker_schedules') && ! Schema::hasTable('doctor_schedules')) {
            Schema::rename('health_worker_schedules', 'doctor_schedules');
        }
    }
};
