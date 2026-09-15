<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link catalogue accounts to their clinical file so patients can open
 * ONLY their own medical details. Plus 2FA confirmation timestamp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('patient_id')->nullable()->after('approved_by')
                ->constrained('patients')->nullOnDelete();
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('patient_id');
            $table->dropColumn('two_factor_confirmed_at');
        });
    }
};
