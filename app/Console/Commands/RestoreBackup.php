<?php

namespace App\Console\Commands;

use App\Services\DhpAuditLogger;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

/**
 * Restore an encrypted backup archive into MySQL. CLI only, never the web.
 * Accepts a bare archive name on the dhp_backups disk; refuses traversal,
 * requires the archive password, and refuses production without --force.
 */
class RestoreBackup extends Command
{
    protected $signature = 'backup:restore {file : Archive name on the backup disk} {--force : Skip confirmation}';

    protected $description = 'Restore an encrypted database backup archive.';

    public function handle(): int
    {
        $file = (string) $this->argument('file');
        $force = (bool) $this->option('force');

        if (! $this->isSafeBasename($file)) {
            $this->error('Give only the archive name, for example example-backup.zip.');
            $this->audit('backup_restore_failed', ['stage' => 'archive_validate']);

            return self::FAILURE;
        }

        $disk = Storage::disk('dhp_backups');

        // The package stores archives in dated subdirectories; resolve the
        // bare name safely without ever accepting paths or traversal.
        $relative = $this->locateArchive($disk, $file);

        if ($relative === null || $disk->size($relative) <= 0) {
            $this->error('Archive not found or empty on the backup disk.');
            $this->audit('backup_restore_failed', ['stage' => 'archive_validate']);

            return self::FAILURE;
        }

        $password = (string) env('BACKUP_ARCHIVE_PASSWORD', '');

        if ($password === '') {
            $this->error('Backup archive password is not configured.');
            $this->audit('backup_restore_failed', ['stage' => 'archive_password']);

            return self::FAILURE;
        }

        if (app()->isProduction() && ! $force) {
            $this->error('Refusing to restore in production without --force.');
            $this->audit('backup_restore_failed', ['stage' => 'production_guard']);

            return self::FAILURE;
        }

        if (! $force) {
            if (! $this->input->isInteractive()) {
                $this->error('Restore overwrites the current database. Rerun with --force or answer the prompt interactively.');

                return self::FAILURE;
            }

            if (! $this->confirm('This OVERWRITES the current database. Continue?')) {
                $this->line('Restore cancelled.');

                return self::SUCCESS;
            }
        }

        $this->audit('backup_restore_started', ['source' => 'cli']);

        $tempDir = storage_path('app/backup-restore-temp/'.Str::uuid()->toString());

        try {
            @mkdir($tempDir, 0700, true);

            $zip = new ZipArchive();
            $absolute = $disk->path($relative);

            if ($zip->open($absolute) !== true) {
                throw new \RuntimeException('Archive could not be opened.');
            }

            $zip->setPassword($password);

            if (! $zip->extractTo($tempDir)) {
                throw new \RuntimeException('Archive could not be decrypted.');
            }

            $zip->close();

            $dumps = $this->findSqlDumps($tempDir);

            if (count($dumps) !== 1) {
                throw new \RuntimeException('Archive must contain exactly one SQL dump.');
            }

            $this->importDump($dumps[0]);

            $this->audit('backup_restore_completed', ['source' => 'cli']);
            $this->info('Database restored successfully.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('DHP backup restore failed.', ['error' => $exception->getMessage()]);

            $this->audit('backup_restore_failed', ['stage' => 'archive_extract']);
            $this->error('Restore failed before completion.');

            return self::FAILURE;
        } finally {
            $this->removeDirectory($tempDir);
            @rmdir(dirname($tempDir));
        }
    }

    /**
     * @return array<int, string> Absolute paths of .sql files, any depth.
     */
    private function findSqlDumps(string $dir): array
    {
        $found = [];

        foreach (scandir($dir) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$name;

            if (is_dir($path)) {
                array_push($found, ...$this->findSqlDumps($path));
            } elseif (str_ends_with(strtolower($name), '.sql')) {
                $found[] = $path;
            }
        }

        return $found;
    }

    private function isSafeBasename(string $file): bool
    {
        return $file !== ''
            && basename($file) === $file
            && ! str_contains($file, '..')
            && (bool) preg_match('/^[A-Za-z0-9_][A-Za-z0-9_.\-]*\.zip$/', $file);
    }

    /**
     * Find the archive by bare name anywhere on the disk. Refuses when
     * zero or several files share the name.
     */
    private function locateArchive(Filesystem $disk, string $file): ?string
    {
        $matches = [];

        foreach ($disk->allFiles() as $path) {
            if (! str_ends_with(strtolower($path), '.zip')) {
                continue;
            }

            if (basename(str_replace('\\', '/', $path)) === $file) {
                $matches[] = $path;
            }
        }

        return count($matches) === 1 ? $matches[0] : null;
    }

    /**
     * @throws \RuntimeException
     */
    private function importDump(string $sqlFile): void
    {
        $connection = config('database.connections.mysql');
        $dumpPath = trim((string) ($connection['dump']['dump_binary_path'] ?? ''));
        $binary = ($dumpPath !== '' ? rtrim($dumpPath, '/\\').DIRECTORY_SEPARATOR : '').'mysql';

        $command = implode(' ', [
            escapeshellcmd($binary),
            '--host='.escapeshellarg((string) $connection['host']),
            '--port='.escapeshellarg((string) $connection['port']),
            '--user='.escapeshellarg((string) $connection['username']),
            '--password='.escapeshellarg((string) $connection['password']),
            escapeshellarg((string) $connection['database']),
        ]).' < '.escapeshellarg($sqlFile);

        $process = Process::fromShellCommandline($command, null, null, null, 300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Database import failed.');
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$name;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }

    private function audit(string $action, array $details): void
    {
        DhpAuditLogger::log(
            user: null,
            action: $action,
            entityType: 'backup',
            entityId: null,
            details: $details,
        );
    }
}
