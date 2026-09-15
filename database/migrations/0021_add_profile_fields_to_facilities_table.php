<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Public profile fields a facility admin maintains from the
     * admin dashboard (system settings). Shown on the login page
     * footer, departments and services sections.
     */
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('email');
            $table->string('secondary_phone')->nullable()->after('phone_number');
            $table->string('website')->nullable()->after('email');
            $table->string('working_hours')->nullable()->after('email');
            $table->text('map_url')->nullable()->after('address');
            $table->json('services')->nullable()->after('map_url');
            $table->json('departments')->nullable()->after('services');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'secondary_phone',
                'website',
                'working_hours',
                'map_url',
                'services',
                'departments',
            ]);
        });
    }
};
