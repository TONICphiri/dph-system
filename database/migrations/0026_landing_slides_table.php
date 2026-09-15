<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Landing hero slideshow managed by the main (national) admin —
 * NOT the facility admin. One image shows at a time and rotates
 * after the admin-set interval (settings: landing_slide_interval).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_slides', function (Blueprint $table) {
            $table->id();
            $table->string('image_path');
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_slides');
    }
};
