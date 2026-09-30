<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5: allow recording superseded (replaced) verification outcomes.
 * Additive only: widens the result set, touches no existing rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE verifications MODIFY result ENUM('valid', 'expired', 'revoked', 'superseded', 'invalid', 'not_found')");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE verifications SET result = 'revoked' WHERE result = 'superseded'");
        DB::statement("ALTER TABLE verifications MODIFY result ENUM('valid', 'expired', 'revoked', 'invalid', 'not_found')");
    }
};
