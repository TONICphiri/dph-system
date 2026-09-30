<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7: idempotency tracking for scheduled notifications.
 * "queued" means dispatched to Laravel's queue, never delivered/read.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('notifiable_type')->nullable();
            $table->unsignedBigInteger('notifiable_id')->nullable();
            $table->string('notification_type', 100)->index();
            $table->string('related_type', 100)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('status', 20)->default('queued')->index();
            $table->timestamp('queued_at')->useCurrent();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->unique(['notification_type', 'related_type', 'related_id'], 'notification_deliveries_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
