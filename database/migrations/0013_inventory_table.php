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
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('medication_name');
            $table->string('medication_code')->nullable();
            $table->string('strength')->nullable()->comment('e.g., 500mg, 100ml');
            $table->integer('current_stock');
            $table->integer('minimum_stock')->default(10);
            $table->integer('maximum_stock')->default(100);
            $table->string('unit_of_measurement')->default('tablets')->comment('tablets, bottles, ampoules, etc.');
            $table->date('expiry_date')->nullable();
            $table->decimal('unit_price', 8, 2)->nullable();
            $table->enum('status', ['available', 'low_stock', 'out_of_stock', 'expired'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamp('last_restocked_at')->nullable();
            $table->foreignId('last_restocked_by_user_id')->nullable()->constrained('users')->nullifyOnDelete();
            $table->timestamps();
            $table->index(['facility_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
