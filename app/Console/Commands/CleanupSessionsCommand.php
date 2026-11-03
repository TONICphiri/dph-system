<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CleanupSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:cleanup
                            {--hours=24 : Remove sessions older than this many hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove expired sessions to keep the sessions table small';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $driver = config('session.driver');

        if ($driver === 'database') {
            $deleted = DB::table('sessions')
                ->where('last_activity', '<', $cutoff->timestamp)
                ->delete();

            $this->info("Deleted {$deleted} expired database sessions older than {$hours} hour(s).");
        } elseif ($driver === 'redis') {
            $this->info('Redis sessions expire automatically via TTL. No manual cleanup needed.');
            $this->info('Consider running Redis memory optimization if needed.');
        } else {
            $this->warn("Session driver '{$driver}' does not require cleanup.");
        }

        return 0;
    }
}
