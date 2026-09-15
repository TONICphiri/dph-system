<?php

namespace App\Console\Commands;

use App\Models\SyncQueue;
use App\Services\SyncService;
use Illuminate\Console\Command;

class ProcessSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the pending sync queue and push records to the national database';

    /**
     * Execute the console command.
     *
     * Runs synchronously (no queue worker needed) so it works on plain
     * XAMPP installs: every scheduler tick uploads whatever is due.
     */
    public function handle(): int
    {
        $due = SyncQueue::dueForSync()->count();

        if ($due === 0) {
            $this->info('No records due for sync.');
            return self::SUCCESS;
        }

        $results = SyncService::processPending(200);

        $this->info("Synced: {$results['synced']}, failed: {$results['failed']}, rejected: {$results['rejected']}.");

        return self::SUCCESS;
    }
}