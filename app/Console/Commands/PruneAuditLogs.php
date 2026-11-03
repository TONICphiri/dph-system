<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneAuditLogs extends Command
{
    /**
     * @var string
     */
    protected $signature = 'audit:prune {--days= : Keep entries newer than this many days}';

    /**
     * @var string
     */
    protected $description = 'Remove audit log entries older than the retention period';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('security.audit_retention_days', 365));

        if ($days < 1) {
            $this->error('The retention period must be at least one day.');

            return self::FAILURE;
        }

        // AuditLog blocks deletes at the model layer to protect the trail from
        // application code. A scheduled retention job is an operator action, so
        // it deletes through the query builder.
        $deleted = DB::table('audit_logs')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Deleted {$deleted} audit log entr".($deleted === 1 ? 'y' : 'ies')." older than {$days} day(s).");

        return self::SUCCESS;
    }
}
