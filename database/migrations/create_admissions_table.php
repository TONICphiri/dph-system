<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->nullifyOnDelete();
            $table->string('ward_name')->nullable();
            $table->string('bed_number')->nullable();
            $table->text('admission_reason');
            $table->foreignId('admitted_by_user_id')->constrained('users')->nullifyOnDelete();
            $table->timestamp('admitted_at');
            $table->text('discharge_summary')->nullable();
            $table->enum('discharge_status', ['Improved', 'Not Improved', 'Referred', 'Left Against Medical Advice', 'Deceased'])->nullable();
            $table->foreignId('discharged_by_user_id')->nullable()->constrained('users')->nullifyOnDelete();
            $table->timestamp('discharged_at')->nullable();
            $table->text('follow_up_instructions')->nullable();
            $table->enum('status', ['active', 'discharged', 'transferred'])->default('active');
            $table->timestamps();
            $table->index(['patient_id', 'status']);
            $table->index(['facility_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
