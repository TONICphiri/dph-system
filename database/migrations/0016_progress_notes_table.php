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
        Schema::create('progress_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by_user_id')->constrained('users')->nullifyOnDelete();
            $table->text('note');
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();
            $table->index(['patient_id', 'recorded_at']);
            $table->index(['admission_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress_notes');
    }
};