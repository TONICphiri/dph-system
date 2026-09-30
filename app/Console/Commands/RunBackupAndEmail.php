<?php

namespace App\Console\Commands;

use App\Mail\Dhp\BackupArchiveMail;
use App\Models\BackupRun;
use App\Models\User;
use App\Notifications\Dhp\BackupFailedNotification;
use App\Services\DhpAuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Encrypted database-only backup with optional emailed copy.
 * Never deletes existing backups on failure; archives stay encrypted
 * with BACKUP_ARCHIVE_PASSWORD and off the public disk.
 */
class RunBackupAndEmail extends Command
{
    protected $signature = 'backup:run-and-email';

    protected $description = 'Run the encrypted database backup and optionally email it.';

    public function handle(): int
    {
        if (! app()->runningUnitTests() && trim((string) config('backup.backup.password', '')) === '') {
            $this->failSafe('archive_password', 'Backup archive password is not configured.');

            return self::FAILURE;
        }

        $lock = Cache::lock('dhp-backup-run', 1800);

        if (! $lock->get()) {
            $this->line('Another backup run is already in progress.');

            return self::FAILURE;
        }

        $run = BackupRun::create([
            'status' => 'failed',
            'backup_disk' => 'dhp_backups',
            'email_attached' => false,
            'started_at' => now(),
            'failure_stage' => 'in_progress',
        ]);

        try {
            $started = microtime(true);

            $exit = Artisan::call('backup:run', ['--only-db' => true, '--disable-notifications' => true]);

            if ($exit !== 0) {
                throw new \RuntimeException('Package backup command exited with code '.$exit.'.');
            }

            $archive = $this->newestArchive();

            if ($archive === null) {
                throw new \RuntimeException('No backup archive was produced.');
            }

            $size = Storage::disk('dhp_backups')->size($archive);

            if ($size <= 0) {
                throw new \RuntimeException('Backup archive is empty.');
            }

            $emailAttached = $this->maybeEmail($archive, $size);

            $run->update([
                'status' => 'success',
                'archive_size_bytes' => $size,
                'completed_at' => now(),
                'failure_stage' => null,
                'email_attached' => $emailAttached,
            ]);

            DhpAuditLogger::log(
                user: null,
                action: 'backup_completed',
                entityType: 'backup',
                entityId: $run->id,
                details: [
                    'archive_size_bytes' => $size,
                    'backup_disk' => 'dhp_backups',
                    'email_attached' => $emailAttached,
                ],
            );

            $this->info('Backup completed in '.round(microtime(true) - $started, 1).'s.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('DHP backup run failed.', ['error' => $exception->getMessage()]);

            $run->update(['status' => 'failed', 'completed_at' => now(), 'failure_stage' => 'archive_creation']);

            $this->failSafe('archive_creation', 'Backup failed before completion. Earlier backups were kept.');

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }

    /**
     * @return string|null Relative path of the newest zip on dhp_backups.
     */
    public function newestArchive(): ?string
    {
        $disk = Storage::disk('dhp_backups');
        $newest = null;
        $newestTime = -1;

        foreach ($disk->allFiles() as $file) {
            if (! str_ends_with(strtolower($file), '.zip')) {
                continue;
            }

            $time = $disk->lastModified($file);

            if ($time > $newestTime) {
                $newestTime = $time;
                $newest = $file;
            }
        }

        return $newest;
    }

    private function maybeEmail(string $archive, int $size): bool
    {
        $recipient = trim((string) env('BACKUP_EMAIL_RECIPIENT', ''));

        if ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $maxMb = (int) env('BACKUP_EMAIL_MAX_MB', 20);
        $disk = Storage::disk('dhp_backups');

        try {
            if ($size > $maxMb * 1024 * 1024) {
                Mail::mailer('smtp_backup')->to($recipient)->queue(new BackupArchiveMail(attached: false));

                return false;
            }

            Mail::mailer('smtp_backup')->to($recipient)->queue(new BackupArchiveMail(
                attached: true,
                archivePath: $disk->path($archive),
                archiveName: 'digital-health-passport-backup-encrypted.zip',
            ));

            return true;
        } catch (Throwable $exception) {
            Log::error('DHP backup email failed.', ['error' => $exception->getMessage()]);

            DhpAuditLogger::log(
                user: null,
                action: 'backup_failed',
                entityType: 'backup',
                entityId: null,
                details: ['stage' => 'archive_email'],
            );

            return false;
        }
    }

    private function failSafe(string $stage, string $consoleMessage): void
    {
        DhpAuditLogger::log(
            user: null,
            action: 'backup_failed',
            entityType: 'backup',
            entityId: null,
            details: ['stage' => $stage],
        );

        $reference = strtoupper(Str::random(6));

        foreach (User::dhpAdminsWithEmail() as $admin) {
            try {
                $admin->notify(new BackupFailedNotification(
                    $admin->first_name ?? $admin->name,
                    now()->format('j M Y H:i'),
                    $reference,
                ));
            } catch (Throwable $exception) {
                Log::warning('Backup failure alert could not be queued.', ['error' => $exception->getMessage()]);
            }
        }

        $this->error($consoleMessage.' Reference '.$reference.'.');
    }
}
