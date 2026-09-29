<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1: citizens table (the passport holder).
 * National ID is an identifier, not a password.
 * The legacy patients table is left untouched; data is copied by a later
 * migration and clinical columns are dropped only after approval.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizens', function (Blueprint $table) {
            $table->id();
            // MW-DHP-YYYY-XXXXXX
            $table->string('passport_id', 30)->unique();
            $table->string('national_id', 20)->nullable()->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('sex', 10);
            $table->date('date_of_birth');
            $table->string('district', 100);
            $table->string('village', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            // Kiosk/shared-computer PIN only, stored with Hash::make, never printed.
            $table->string('pin_hash')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['last_name', 'first_name']);
            $table->index('date_of_birth');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citizens');
    }
};
