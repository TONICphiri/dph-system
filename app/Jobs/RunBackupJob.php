<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;

/**
 * Runs the encrypted backup off the web request. Overlap with scheduled
 * or other manual runs is blocked by the command's own cache lock.
 */
class RunBackupJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Artisan::call('backup:run-and-email');
    }
}
