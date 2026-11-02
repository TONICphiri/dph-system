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
            $table->string('next_of_kin_name', 150)->nullable()->after('provisional_reason_note');
            $table->string('next_of_kin_relationship', 50)->nullable()->after('next_of_kin_name');
            $table->string('next_of_kin_phone', 30)->nullable()->after('next_of_kin_relationship');
            $table->boolean('dob_is_estimated')->default(false)->after('next_of_kin_phone');
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
                'next_of_kin_name',
                'next_of_kin_relationship',
                'next_of_kin_phone',
                'dob_is_estimated',
                'provisional_registered_at',
                'identity_completed_at',
            ]);
        });
    }
};
