<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\BackupFailed;
use App\Notifications\BackupSucceeded;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;

class BackupDatabaseCommand extends Command
{
    /**
     * php artisan backup:run
     *
     * Dumps the current database connection to storage/app/backups,
     * gzips it, deletes backups older than the retention window, and
     * notifies every user with the 'view_audit_logs' permission
     * (i.e. admins / hospital administrators) about the outcome.
     */
    protected $signature = 'backup:run {--keep-days=14 : How many days of backups to retain}';

    protected $description = 'Back up the DHP database and notify admins of the result';

    public function handle(): int
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        $directory = storage_path('app/backups');
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0750, true);
        }

        $timestamp = now()->format('Y-m-d_His');
        $filename = "dhp-backup-{$timestamp}.sql";
        $path = "{$directory}/{$filename}";

        try {
            match ($driver) {
                'mysql', 'mariadb' => $this->backupMysql($connection, $path),
                'sqlite' => $this->backupSqlite($path),
                default => throw new \RuntimeException("Backup not implemented for driver [{$driver}]"),
            };

            $this->gzip($path);
            $gzPath = "{$path}.gz";
            $sizeMb = round(filesize($gzPath) / 1024 / 1024, 2);

            $this->pruneOldBackups($directory, (int) $this->option('keep-days'));

            $this->info("Backup complete: {$gzPath} ({$sizeMb} MB)");
            $this->notifyAdmins(new BackupSucceeded($filename.'.gz', $sizeMb, now()));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            \Log::error('Database backup failed', ['error' => $e->getMessage()]);
            $this->error('Backup failed: '.$e->getMessage());
            $this->notifyAdmins(new BackupFailed($e->getMessage(), now()));

            return self::FAILURE;
        }
    }

    /**
     * mysqldump-based backup. Requires the mysqldump binary on PATH.
     * Avoids putting the password on the process list by using MYSQL_PWD env var.
     */
    protected function backupMysql(string $connection, string $path): void
    {
        $config = config("database.connections.{$connection}");

        $command = sprintf(
            'mysqldump --no-tablespaces --single-transaction --quick --host=%s --port=%s --user=%s %s > %s',
            escapeshellarg($config['host']),
            escapeshellarg((string) $config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($config['database']),
            escapeshellarg($path)
        );

        $process = proc_open(
            $command,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            array_merge($_ENV, ['MYSQL_PWD' => $config['password']])
        );

        if (! is_resource($process)) {
            throw new \RuntimeException('Could not start mysqldump process');
        }

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new \RuntimeException("mysqldump exited with code {$exitCode}: {$stderr}");
        }
    }

    protected function backupSqlite(string $path): void
    {
        $dbFile = config('database.connections.sqlite.database');
        File::copy($dbFile, $path);
    }

    protected function gzip(string $path): void
    {
        $data = File::get($path);
        $gz = gzencode($data, 9);
        File::put("{$path}.gz", $gz);
        File::delete($path);
    }

    protected function pruneOldBackups(string $directory, int $keepDays): void
    {
        $cutoff = now()->subDays($keepDays);

        foreach (File::files($directory) as $file) {
            if (now()->timestamp - $file->getMTime() > $keepDays * 86400) {
                File::delete($file->getPathname());
            }
        }
    }

    protected function notifyAdmins($notification): void
    {
        $admins = User::whereHas('roles.permissions', function ($q) {
            $q->where('name', 'view_audit_logs');
        })->orWhereHas('permissions', function ($q) {
            $q->where('name', 'view_audit_logs');
        })->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, $notification);
    }
}
