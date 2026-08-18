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
        Schema::create('vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->decimal('temperature', 5, 2)->nullable()->comment('Celsius');
            $table->integer('systolic_bp')->nullable()->comment('mm Hg');
            $table->integer('diastolic_bp')->nullable()->comment('mm Hg');
            $table->integer('heart_rate')->nullable()->comment('bpm');
            $table->integer('respiratory_rate')->nullable()->comment('breaths/min');
            $table->decimal('weight', 6, 2)->nullable()->comment('kg');
            $table->decimal('height', 5, 2)->nullable()->comment('cm');
            $table->decimal('muac', 4, 1)->nullable()->comment('Mid-Upper Arm Circumference (mm)');
            $table->integer('oxygen_saturation')->nullable()->comment('SpO2 %');
            $table->enum('priority_level', ['Low', 'Medium', 'High', 'Emergency'])->default('Low');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullifyOnDelete();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();
            $table->index(['patient_id', 'recorded_at']);
            $table->index('priority_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vitals');
    }
};
