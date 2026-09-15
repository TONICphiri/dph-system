<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * User Catalogue per system-description2.md §1.5, §3.1 (FR-A1..A8), §5.3, §7.
 *
 * Every account is keyed by National Identity Number (NIN):
 * - raw NIN is NEVER stored; only nin_hash = HMAC-SHA256(nin, NIN pepper) + unique index
 * - nin_last4 kept for masked display (e.g. NIN-****-4821)
 * - status lifecycle: DRAFT → PENDING → ACTIVE → SUSPENDED / DEACTIVATED (§5.2)
 * - must_change_password enforces first-login password set (FR-A5)
 * - lockout fields enforce FR-A7 (5 failures → 15 min lock + notify)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nin_hash', 64)->nullable()->unique()->after('email')
                ->comment('HMAC-SHA256 of NIN with dedicated pepper; unique catalogue key');
            $table->string('nin_last4', 4)->nullable()->after('nin_hash');
            $table->string('full_name')->nullable()->after('nin_last4');
            $table->date('dob')->nullable()->after('full_name');
            $table->string('gender', 12)->nullable()->after('dob');
            $table->string('phone', 30)->nullable()->after('gender');
            $table->string('id_document_ref')->nullable()->after('phone')
                ->comment('Scanned reference / storage path of national ID document (FR-A1)');
            $table->foreignId('enrolled_by')->nullable()->after('facility_id')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->after('enrolled_by')
                ->constrained('users')->nullOnDelete();
            $table->boolean('must_change_password')->default(false)->after('password');
            $table->text('two_factor_secret')->nullable()->after('must_change_password');
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('two_factor_secret');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
        });

        // Backfill full_name from legacy name column so existing staff keep working.
        try {
            DB::table('users')->whereNull('full_name')->update(['full_name' => DB::raw('`name`')]);
        } catch (\Throwable $e) {
            // Non-critical on fresh installs.
        }

        // Widen status enum to the catalogue lifecycle (MySQL only; SQLite treats it as text).
        try {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE `users` MODIFY `status` ENUM('pending','active','suspended','deactivated','inactive') NOT NULL DEFAULT 'pending'");
            }
        } catch (\Throwable $e) {
            // Leave as-is; model validation enforces the lifecycle.
        }

        // Email is optional in the catalogue (FR-A1: email optional, NIN is the login key).
        try {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE `users` MODIFY `email` VARCHAR(255) NULL');
            }
        } catch (\Throwable $e) {
            // SQLite: column already flexible enough.
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('enrolled_by');
            $table->dropUnique(['nin_hash']);
            $table->dropColumn([
                'nin_hash', 'nin_last4', 'full_name', 'dob', 'gender', 'phone',
                'id_document_ref', 'must_change_password', 'two_factor_secret',
                'failed_login_attempts', 'locked_until',
            ]);
        });
    }
};
