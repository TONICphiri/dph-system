<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1: additive is_active flag on facilities.
 * Mirrors the existing status column; district stays normalized via
 * district_id (spec "district" is exposed through the model accessor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            if (! Schema::hasColumn('facilities', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
        });

        DB::table('facilities')->where('status', '!=', 'active')->update(['is_active' => false]);
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            if (Schema::hasColumn('facilities', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
