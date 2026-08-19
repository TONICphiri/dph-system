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
        Schema::create('sync_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->nullable()->constrained()->nullifyOnDelete();
            $table->string('record_type')->comment('patients, encounters, vitals, prescriptions, admissions');
            $table->unsignedBigInteger('record_id');
            $table->enum('action', ['create', 'update', 'delete'])->default('create');
            $table->json('payload')->comment('Full record data for sync');
            $table->enum('status', ['pending', 'synced', 'failed'])->default('pending');
            $table->integer('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->index(['facility_id', 'status']);
            $table->index(['record_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_queue');
    }
};
