<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sync-on-restore: failed pushes wait with exponential backoff and are
 * picked up automatically once the internet is back — no manual retry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_queue', function (Blueprint $table) {
            $table->timestamp('next_retry_at')->nullable()->after('retry_count');
            $table->index(['status', 'next_retry_at']);
        });
    }

    public function down(): void
    {
        Schema::table('sync_queue', function (Blueprint $table) {
            $table->dropIndex(['status', 'next_retry_at']);
            $table->dropColumn('next_retry_at');
        });
    }
};
