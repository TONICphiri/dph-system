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
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('facility_code')->unique()->comment('MOH facility code');
            $table->enum('facility_type', ['Hospital', 'Health Centre', 'Clinic'])->default('Health Centre');
            $table->string('district');
            $table->string('region')->nullable();
            $table->text('address')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
