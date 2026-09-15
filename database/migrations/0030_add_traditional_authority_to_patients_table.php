<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Traditional authority (T/A), collected at first registration
     * alongside village and district, per the National ID details.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('traditional_authority')->nullable()->after('village');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('traditional_authority');
        });
    }
};
