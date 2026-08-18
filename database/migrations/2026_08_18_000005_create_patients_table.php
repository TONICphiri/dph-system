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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('national_id')->unique()->comment('National ID of patient');
            $table->string('dhp_id')->unique()->comment('Digital Health Passport ID (DHP-YYYY-XXXXXXXX)');
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['M', 'F', 'Other'])->nullable();
            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();
            $table->string('village')->nullable();
            $table->string('district')->nullable();
            $table->enum('status', ['active', 'inactive', 'deceased'])->default('active');
            $table->boolean('is_child')->default(false)->comment('Flag for pediatric patients without National ID');
            $table->foreignId('guardian_id')->nullable()->constrained('guardians')->cascadeOnDelete();
            $table->timestamp('registered_at')->useCurrent();
            $table->foreignId('registered_by_facility_id')->nullable()->constrained('facilities')->nullifyOnDelete();
            $table->foreignId('registered_by_user_id')->nullable()->constrained('users')->nullifyOnDelete();
            $table->timestamps();
            $table->index('national_id');
            $table->index('dhp_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
