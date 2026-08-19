<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSyncQueue;
use App\Models\SyncQueue;
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
     */
    public function handle(): int
    {
        $pending = SyncQueue::where('status', 'pending')->count();

        if ($pending === 0) {
            $this->info('No pending sync records.');
            return self::SUCCESS;
        }

        $this->info("Dispatching sync job for {$pending} pending record(s)...");

        ProcessSyncQueue::dispatch();

        return self::SUCCESS;
    }
}