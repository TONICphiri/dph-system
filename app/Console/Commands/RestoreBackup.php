<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RestoreBackup extends Command
{
    protected $signature = 'backup:restore {file : File name from the Backups page, or a full path} {--force : Do not ask for confirmation}';

    protected $description = 'Replace the data in the database with the contents of a backup file';

    public function handle(BackupService $backups): int
    {
        $name = $this->argument('file');
        $disk = Storage::disk(config('health_passport.backup.disk'));
        $stored = config('health_passport.backup.directory').'/'.basename($name);

        $path = null;
        $temporary = null;

        if (is_file($name)) {
            $path = $name;
        } elseif ($disk->exists($stored)) {
            $stream = $disk->readStream($stored);

            if (! is_resource($stream)) {
                $this->error('That backup file could not be read.');

                return self::FAILURE;
            }

            $temporary = tempnam(sys_get_temp_dir(), 'dhp-restore-');
            $target = $temporary === false ? false : fopen($temporary, 'wb');

            if ($target === false) {
                fclose($stream);
                $this->error('A temporary file for the backup could not be created.');

                return self::FAILURE;
            }

            stream_copy_to_stream($stream, $target);
            fclose($target);
            fclose($stream);
            $path = $temporary;
        }

        if (! $path) {
            $this->error('That backup file could not be found.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('This replaces all current data with the backup. Continue?')) {
            if ($temporary !== null && is_file($temporary)) {
                @unlink($temporary);
            }

            return self::FAILURE;
        }

        try {
            $restored = $backups->restore($path);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            if ($temporary !== null && is_file($temporary)) {
                @unlink($temporary);
            }
        }

        foreach ($restored as $table => $count) {
            $this->line("{$table}: {$count} rows");
        }

        $this->info('The backup has been restored.');

        return self::SUCCESS;
    }
}
