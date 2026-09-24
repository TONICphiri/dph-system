<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->restrictOnDelete();
            $table->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bed_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('discharged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('awaiting_bed')->index();
            $table->text('admission_reason');
            $table->string('preferred_ward_type', 50)->nullable();
            $table->timestamp('admitted_at');
            $table->timestamp('bed_allocated_at')->nullable();
            $table->timestamp('discharged_at')->nullable();
            $table->string('discharge_outcome', 30)->nullable();
            $table->text('discharge_summary')->nullable();
            $table->text('follow_up_instructions')->nullable();
            $table->timestamps();
        });

        Schema::create('progress_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note');
            $table->timestamps();
        });

        Schema::create('medication_administrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prescription_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('given_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('given_at');
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        foreach (['vitals', 'prescriptions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreign('admission_id')->references('id')->on('admissions')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['vitals', 'prescriptions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['admission_id']);
            });
        }
        Schema::dropIfExists('medication_administrations');
        Schema::dropIfExists('progress_notes');
        Schema::dropIfExists('admissions');
    }
};
