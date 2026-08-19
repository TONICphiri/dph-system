<?php

namespace App\Jobs;

use App\Services\SyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessSyncQueue implements ShouldQueue
{
    use Queueable;

    /**
     * Number of pending records to process per run
     */
    public int $limit;

    /**
     * Create a new job instance.
     */
    public function __construct(int $limit = 50)
    {
        $this->limit = $limit;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $results = SyncService::processPending($this->limit);

        \Log::info('Sync queue processed', $results);

        if (($results['failed'] ?? 0) > 0) {
            // Re-queue a follow-up job so failures are retried with backoff
            self::dispatch($this->limit)->delay(now()->addSeconds(60));
        }
    }
}