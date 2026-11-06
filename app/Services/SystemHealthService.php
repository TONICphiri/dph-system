<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Collects the technical status shown on the System Health page.
 */
class SystemHealthService
{
    /**
     * @return array<int, array{label: string, value: string, healthy: bool}>
     */
    public function checks(): array
    {
        return [
            $this->databaseCheck(),
            $this->migrationsCheck(),
            $this->storageCheck(),
            $this->backupStorageCheck(),
            $this->cronCheck(),
            $this->mailCheck(),
            $this->smsCheck(),
            $this->queueCheck(),
            $this->failedJobsCheck(),
            [
                'label' => 'Application environment',
                'value' => ucfirst(app()->environment()).(config('app.debug') ? ', debug mode on' : ''),
                'healthy' => ! (app()->isProduction() && config('app.debug')),
            ],
            ['label' => 'PHP version', 'value' => PHP_VERSION, 'healthy' => version_compare(PHP_VERSION, '8.2.0', '>=')],
            ['label' => 'Laravel version', 'value' => app()->version(), 'healthy' => true],
        ];
    }

    /**
     * @return array<string, int>
     */
    public function totals(): array
    {
        return [
            'facilities' => Facility::query()->count(),
            'users' => User::query()->count(),
            'patients' => Patient::query()->count(),
        ];
    }

    private function databaseCheck(): array
    {
        try {
            $connection = DB::connection();
            $database = $connection->getDatabaseName();
            $driver = $connection->getDriverName();

            $bytes = 0;
            if ($driver === 'mysql') {
                $bytes = (int) DB::table('information_schema.tables')
                    ->where('table_schema', $database)
                    ->sum(DB::raw('data_length + index_length'));
            }

            $value = $bytes > 0
                ? "Connected to {$database}, ".number_format($bytes / 1048576, 2).' MB used'
                : "Connected to {$database} ({$driver})";

            return [
                'label' => 'Database connection',
                'value' => $value,
                'healthy' => true,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return ['label' => 'Database connection', 'value' => 'Not reachable', 'healthy' => false];
        }
    }

    /**
     * A half applied migration is the usual reason a page that needs a newer
     * table or column fails, so the status is shown here before it is seen as
     * an error on a working screen.
     */
    private function migrationsCheck(): array
    {
        try {
            $files = app('migrator')->getMigrationFiles(database_path('migrations'));
            $ran = app('migration.repository')->getRan();
            $pending = array_values(array_diff(array_keys($files), $ran));
            $count = count($pending);

            return [
                'label' => 'Database schema',
                'value' => $count === 0
                    ? 'Up to date'
                    : $count.' migration'.($count === 1 ? '' : 's').' pending. Run php artisan migrate.',
                'healthy' => $count === 0,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return ['label' => 'Database schema', 'value' => 'Could not read the migration status', 'healthy' => false];
        }
    }

    private function storageCheck(): array
    {
        $writable = is_writable(storage_path('logs')) && is_writable(storage_path('framework'));

        return [
            'label' => 'File storage',
            'value' => $writable ? 'Writable' : 'Not writable. Check folder permissions for storage.',
            'healthy' => $writable,
        ];
    }

    /**
     * A durable disk is required in production. A local disk is only a warning
     * on a single server, but it would lose every copy on an ephemeral host.
     */
    private function backupStorageCheck(): array
    {
        $disk = (string) config('health_passport.backup.disk', 'local');
        $durable = $disk !== 'local' || ! app()->isProduction();

        return [
            'label' => 'Backup storage',
            'value' => $durable
                ? "Using the {$disk} disk"
                : 'Backups are stored on the local disk. Set BACKUP_DISK=s3 for an off-server copy.',
            'healthy' => $durable,
        ];
    }

    /**
     * The cron secret protects the optional endpoints on hosts without a
     * system scheduler. A server cron running schedule:run is equally valid.
     */
    private function cronCheck(): array
    {
        $configured = filled(config('health_passport.cron_secret'));

        return [
            'label' => 'Scheduled jobs',
            'value' => $configured
                ? 'Protected cron endpoint configured'
                : 'Using the server cron (schedule:run). Set CRON_SECRET only if the HTTP cron endpoints are used.',
            'healthy' => true,
        ];
    }

    private function mailCheck(): array
    {
        $driver = (string) config('mail.default', 'log');
        $healthy = $driver !== 'log' || ! app()->isProduction();

        return [
            'label' => 'Email delivery',
            'value' => $healthy
                ? "Using the {$driver} mailer"
                : 'Mail is still set to log, so no email leaves the server. Set MAIL_MAILER in production.',
            'healthy' => $healthy,
        ];
    }

    private function smsCheck(): array
    {
        $enabled = app(SettingService::class)->get('sms_notifications_enabled') === '1';
        $driver = (string) config('services.sms.driver', 'log');
        $gatewayStatus = $enabled
            ? $this->smsGatewayStatus($driver)
            : ['value' => 'Not enabled', 'healthy' => true];

        return [
            'label' => 'Text message delivery',
            'value' => $gatewayStatus['value'],
            'healthy' => $gatewayStatus['healthy'],
        ];
    }

    /**
     * @return array{value: string, healthy: bool}
     */
    private function smsGatewayStatus(string $driver): array
    {
        if ($driver === 'log') {
            return [
                'value' => 'SMS notifications are enabled, but SMS_DRIVER is log. Messages are only logged, not sent. Configure a real SMS provider.',
                'healthy' => false,
            ];
        }

        if ($driver === 'twilio') {
            $config = config('services.twilio', []);
            $missing = [];

            if (empty($config['sid'])) {
                $missing[] = 'TWILIO_SID';
            }
            if (empty($config['token'])) {
                $missing[] = 'TWILIO_TOKEN';
            }
            if (empty($config['from']) && empty($config['messaging_service_sid'])) {
                $missing[] = 'TWILIO_FROM or TWILIO_MESSAGING_SERVICE_SID';
            }

            return $this->smsProviderStatus('Twilio', $missing);
        }

        if ($driver === 'africastalking') {
            $config = config('services.africastalking', []);
            $missing = [];

            if (empty($config['username'])) {
                $missing[] = 'AFRICASTALKING_USERNAME';
            }
            if (empty($config['key'])) {
                $missing[] = 'AFRICASTALKING_KEY';
            }

            return $this->smsProviderStatus("Africa's Talking", $missing);
        }

        return [
            'value' => "SMS_DRIVER is set to unsupported driver '{$driver}'. Use twilio, africastalking or log.",
            'healthy' => false,
        ];
    }

    /**
     * @param  array<int, string>  $missing
     * @return array{value: string, healthy: bool}
     */
    private function smsProviderStatus(string $provider, array $missing): array
    {
        return empty($missing)
            ? ['value' => "Using the {$provider} gateway; required settings are present.", 'healthy' => true]
            : [
                'value' => "SMS_DRIVER is {$provider}, but configure these server environment values: ".implode(', ', $missing).'.',
                'healthy' => false,
            ];
    }

    private function queueCheck(): array
    {
        $pending = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;

        return [
            'label' => 'Background jobs waiting',
            'value' => (string) $pending,
            'healthy' => $pending < 100,
        ];
    }

    private function failedJobsCheck(): array
    {
        $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

        return [
            'label' => 'Failed background jobs',
            'value' => (string) $failed,
            'healthy' => $failed === 0,
        ];
    }
}
