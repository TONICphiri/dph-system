<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('identity_status', 20)->default('verified')->after('status')->index();
            $table->string('provisional_reason', 100)->nullable()->after('identity_status');
            $table->string('provisional_reason_note')->nullable()->after('provisional_reason');
            $table->string('guardian_name', 150)->nullable()->after('provisional_reason_note');
            $table->string('guardian_relationship', 50)->nullable()->after('guardian_name');
            $table->string('guardian_phone', 30)->nullable()->after('guardian_relationship');
            $table->boolean('dob_is_estimated')->default(false)->after('guardian_phone');
            $table->timestamp('provisional_registered_at')->nullable()->after('dob_is_estimated');
            $table->foreignId('provisional_registered_by')->nullable()->after('provisional_registered_at')->constrained('users')->nullOnDelete();
            $table->timestamp('identity_completed_at')->nullable()->after('provisional_registered_by');
            $table->foreignId('identity_completed_by')->nullable()->after('identity_completed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('provisional_registered_by');
            $table->dropConstrainedForeignId('identity_completed_by');
            $table->dropColumn([
                'identity_status',
                'provisional_reason',
                'provisional_reason_note',
                'guardian_name',
                'guardian_relationship',
                'guardian_phone',
                'dob_is_estimated',
                'provisional_registered_at',
                'identity_completed_at',
            ]);
        });
    }
};
