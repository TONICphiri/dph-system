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
        Schema::create('medication_administrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prescription_id')->nullable()->constrained()->nullifyOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('administered_by_user_id')->constrained('users')->nullifyOnDelete();
            $table->string('medication_name');
            $table->string('dose')->nullable();
            $table->string('route')->nullable()->comment('PO, IV, IM, SC, Topical, etc.');
            $table->timestamp('administered_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'administered_at']);
            $table->index(['admission_id', 'administered_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_administrations');
    }
};