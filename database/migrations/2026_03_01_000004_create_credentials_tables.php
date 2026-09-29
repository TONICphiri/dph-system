<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1: verifiable health credentials + per-type detail tables.
 * QR tokens are random and opaque (Str::random(64), NO personal data).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credentials', function (Blueprint $table) {
            $table->id();
            // MW-CRED-YYYY-XXXXXX
            $table->string('credential_number', 30)->unique();
            $table->foreignId('citizen_id')->constrained('citizens')->cascadeOnDelete();
            $table->foreignId('facility_id')->nullable()->constrained('facilities')->nullOnDelete();
            $table->enum('type', ['vaccination', 'lab_test']);
            $table->enum('status', ['active', 'expired', 'revoked', 'superseded'])->default('active')->index();
            $table->date('issue_date')->index();
            $table->date('expiry_date')->nullable();
            $table->string('qr_token', 64)->unique();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('revocation_reason')->nullable();
            $table->foreignId('replaced_by_credential_id')->nullable()->constrained('credentials')->nullOnDelete();
            $table->timestamps();

            $table->index(['citizen_id', 'status']);
        });

        Schema::create('vaccination_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credential_id')->unique()->constrained('credentials')->cascadeOnDelete();
            $table->string('vaccine_name', 150);
            $table->unsignedTinyInteger('dose_number');
            $table->date('administration_date');
            $table->string('batch_number', 50)->nullable();
            $table->date('next_dose_date')->nullable();
            $table->timestamps();
        });

        Schema::create('test_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credential_id')->unique()->constrained('credentials')->cascadeOnDelete();
            $table->string('test_type', 150);
            $table->date('sample_collection_date');
            $table->date('result_date');
            $table->string('result', 100);
            $table->date('valid_until');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_details');
        Schema::dropIfExists('vaccination_details');
        Schema::dropIfExists('credentials');
    }
};
