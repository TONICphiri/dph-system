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
        Schema::create('encounters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->nullifyOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullifyOnDelete();
            $table->enum('encounter_type', ['OPD', 'Emergency', 'Inpatient', 'Follow-up'])->default('OPD');
            $table->enum('status', ['registered', 'triaged', 'consultation', 'completed', 'admitted'])->default('registered');
            $table->text('chief_complaint')->nullable();
            $table->text('history_of_present_illness')->nullable();
            $table->text('examination_findings')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->boolean('requires_admission')->default(false);
            $table->timestamp('encounter_date');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'encounter_date']);
            $table->index(['facility_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encounters');
    }
};
