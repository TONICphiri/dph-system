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
        Schema::create('lab_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->string('test_type'); // e.g., 'Malaria RDT', 'Blood Sugar', 'CBC', 'Chest X-ray'
            $table->string('test_name');
            $table->string('status')->default('requested'); // requested, pending, results, cancelled
            $table->text('description')->nullable();
            $table->string('requested_by_user_id')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->text('result_description')->nullable();
            $table->string('result_value')->nullable(); // e.g., 'Positive', '120 mg/dL', 'WBC 7.5'
            $table->string('result_units')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['encounter_id', 'status']);
            $table->index('test_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_orders');
    }
};