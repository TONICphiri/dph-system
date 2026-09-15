<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogue support tables per system-description2.md §5.3:
 * qr_credentials, verification_logs, consents, appointments,
 * vaccine_types, test_types, credential_templates, announcements.
 *
 * QR payloads are HMAC-SHA256 signed (NFR-6); only token hashes stored (NFR).
 * Verifier responses are data-minimal (FR-E1); every scan is audited (FR-E2).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vaccine_types')) {
            Schema::create('vaccine_types', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('code', 40)->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('test_types')) {
            Schema::create('test_types', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('code', 40)->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('credential_templates')) {
            Schema::create('credential_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('credential_type', 60);
                $table->unsignedInteger('validity_days')->default(365);
                $table->text('body')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('body');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('qr_credentials')) {
            Schema::create('qr_credentials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()
                    ->comment('Catalogue owner when credential is issued to a users row');
                $table->string('token_hash', 64)->unique();
                $table->text('payload_signed');
                $table->string('credential_type', 60)->default('health_pass');
                $table->enum('status', ['active', 'revoked', 'expired'])->default('active');
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'status']);
                $table->index(['patient_id', 'status']);
            });
        }

        if (! Schema::hasTable('verification_logs')) {
            Schema::create('verification_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('credential_id')->nullable()->constrained('qr_credentials')->nullOnDelete();
                $table->foreignId('verifier_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('holder_nin_hash', 64)->nullable()
                    ->comment('NIN-hash reference only; never raw NIN (FR-E2, NFR-7)');
                $table->ipAddress('ip_address')->nullable();
                $table->string('result', 20)->default('valid');
                $table->timestamp('scanned_at')->useCurrent();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('consents')) {
            Schema::create('consents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
                $table->foreignId('patient_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('grantee_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('scope', 60)->default('full_record');
                $table->timestamp('granted_at')->useCurrent();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('appointments')) {
            Schema::create('appointments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
                $table->foreignId('patient_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('practitioner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('facility_id')->nullable()->constrained('facilities')->nullOnDelete();
                $table->dateTime('scheduled_at');
                $table->enum('status', ['booked', 'rescheduled', 'cancelled', 'completed'])->default('booked');
                $table->string('reason')->nullable();
                $table->timestamps();
                $table->index(['practitioner_id', 'scheduled_at']);
            });
        }
    }

    public function down(): void
    {
        foreach (['appointments', 'consents', 'verification_logs', 'qr_credentials', 'announcements', 'credential_templates', 'test_types', 'vaccine_types'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
