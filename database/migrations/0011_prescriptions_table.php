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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prescribed_by_user_id')->constrained('users')->nullifyOnDelete();
            $table->string('medication_name');
            $table->text('medication_code')->nullable()->comment('NDC or local code');
            $table->string('dose');
            $table->string('frequency')->comment('e.g., 2x daily, 3x daily');
            $table->integer('quantity')->nullable();
            $table->string('duration')->nullable()->comment('e.g., 7 days, 2 weeks');
            $table->text('instructions')->nullable()->comment('Special instructions for patient');
            $table->enum('status', ['pending', 'dispensed', 'completed', 'cancelled'])->default('pending');
            $table->foreignId('dispensed_by_user_id')->nullable()->constrained('users')->nullifyOnDelete();
            $table->timestamp('dispensed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('prescribed_at')->useCurrent();
            $table->timestamps();
            $table->index(['patient_id', 'status']);
            $table->index(['encounter_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
