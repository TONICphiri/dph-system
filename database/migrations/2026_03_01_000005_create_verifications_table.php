<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1: verification attempts. One row + one audit log per attempt.
 * Verifier sees minimal data (enforced in controllers/views, never here).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credential_id')->nullable()->constrained('credentials')->nullOnDelete();
            $table->foreignId('verifier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('method', ['qr_scan', 'manual_code']);
            $table->enum('result', ['valid', 'expired', 'revoked', 'invalid', 'not_found'])->index();
            $table->timestamp('verified_at')->useCurrent();
            $table->string('ip_address', 45)->nullable();

            $table->index(['credential_id', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};
