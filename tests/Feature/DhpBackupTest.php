<?php

namespace Tests\Feature;

use App\Mail\Dhp\BackupArchiveMail;
use App\Models\AuditLog;
use App\Models\BackupRun;
use App\Models\Citizen;
use App\Models\User;
use App\Notifications\Dhp\BackupFailedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 8: encrypted backups, guarded restore, safe admin operations.
 * Backups contain health data and must never leave the server unencrypted.
 */
class DhpBackupTest extends TestCase
{
    use RefreshDatabase;

    // Seed like the legacy suites so every execution order finds demo data.
    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('dhp_backups');
        $this->setBackupPassword('test-backup-password-123');
        $this->removeRestoreTempDir();
    }

    private function removeRestoreTempDir(): void
    {
        $base = storage_path('app/backup-restore-temp');

        if (! is_dir($base)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($base);
    }

    private function setBackupPassword(string $password): void
    {
        putenv('BACKUP_ARCHIVE_PASSWORD='.$password);
        $_ENV['BACKUP_ARCHIVE_PASSWORD'] = $password;
        $_SERVER['BACKUP_ARCHIVE_PASSWORD'] = $password;
        config()->set('backup.backup.password', $password);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function newestZip(): ?string
    {
        $disk = Storage::disk('dhp_backups');
        $newest = null;
        $time = -1;

        foreach ($disk->allFiles() as $file) {
            if (str_ends_with(strtolower($file), '.zip') && $disk->lastModified($file) > $time) {
                $time = $disk->lastModified($file);
                $newest = $file;
            }
        }

        return $newest;
    }

    public function test_backup_targets_only_mysql_with_no_files(): void
    {
        $this->assertSame(['mysql'], config('backup.backup.source.databases'));
        $this->assertSame([], config('backup.backup.source.files.include'));
        $this->assertSame(['dhp_backups'], config('backup.backup.destination.disks'));
        $this->assertSame('test-backup-password-123', config('backup.backup.password'));
    }

    public function test_backup_disk_is_non_public(): void
    {
        $root = config('filesystems.disks.dhp_backups.root');
        $this->assertStringContainsString('app/backups', str_replace('\\', '/', (string) $root));
        $this->assertStringNotContainsString('public', (string) $root);

        foreach (app('router')->getRoutes()->getRoutes() as $route) {
            $this->assertStringNotContainsStringIgnoringCase('download', $route->uri());
        }
    }

    public function test_successful_backup_writes_safe_records_and_encrypted_zip(): void
    {
        $this->artisan('backup:run-and-email')->assertSuccessful();

        $zip = $this->newestZip();
        $this->assertNotNull($zip);

        $run = BackupRun::query()->latest('id')->firstOrFail();
        $this->assertSame('success', $run->status);
        $this->assertGreaterThan(0, $run->archive_size_bytes);
        $this->assertSame('dhp_backups', $run->backup_disk);

        $audit = AuditLog::query()->where('action', 'backup_completed')->firstOrFail();
        $this->assertSame(['archive_size_bytes', 'backup_disk', 'email_attached'], array_keys($audit->details));

        // Encrypted: the SQL dump inside is unreadable without the password.
        $archive = new \ZipArchive;
        $archive->open(Storage::disk('dhp_backups')->path($zip));
        $names = [];
        for ($i = 0; $i < $archive->numFiles; $i++) {
            $names[] = $archive->getNameIndex($i);
        }
        $dumps = array_values(preg_grep('/\.sql$/', $names));
        $this->assertNotEmpty($dumps);
        $this->assertFalse((bool) $archive->getFromName($dumps[0]));
        $archive->setPassword('test-backup-password-123');
        $this->assertNotEmpty($archive->getFromName($dumps[0]));
        $archive->close();
    }

    public function test_failed_backup_keeps_old_archive_and_alerts_admins(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'email' => 'backup.admin@example.com']);
        User::factory()->create(['role' => 'issuer', 'is_active' => true, 'email' => 'issuer.ignored@example.com']);

        $this->artisan('backup:run-and-email')->assertSuccessful();
        $firstZip = $this->newestZip();
        $this->assertNotNull($firstZip);

        config()->set('database.connections.mysql.dump.dump_binary_path', '/nonexistent-dir-xyz');
        $this->artisan('backup:run-and-email')->assertFailed();

        // Old archive untouched, failure audited safely, admins alerted generically.
        $this->assertTrue(Storage::disk('dhp_backups')->exists($firstZip));
        $failed = AuditLog::query()->where('action', 'backup_failed')->firstOrFail();
        $this->assertSame(['stage' => 'archive_creation'], $failed->details);

        Notification::assertSentTo($admin, BackupFailedNotification::class, function ($notification) use ($admin) {
            $mail = $notification->toMail($admin);
            $text = json_encode([$mail->subject, $mail->introLines, $mail->outroLines]);
            $this->assertStringContainsString('backup alert', strtolower($text));
            foreach (['health_passport', '.zip', 'Trace', 'Exception', '/nonexistent'] as $banned) {
                $this->assertStringNotContainsString($banned, $text);
            }

            return true;
        });
        Notification::assertNothingSentTo(User::query()->where('email', 'issuer.ignored@example.com')->first(), BackupFailedNotification::class);

        $mail = (new BackupFailedNotification('Admin', 'now', 'REF'))->toMail($admin);
        $this->assertStringNotContainsString('REF-database', json_encode($mail->introLines));
    }

    public function test_email_attached_only_under_limit_and_blank_recipient_skips(): void
    {
        Mail::fake();
        putenv('BACKUP_EMAIL_RECIPIENT=backup.box@example.com');
        $_ENV['BACKUP_EMAIL_RECIPIENT'] = 'backup.box@example.com';
        $_SERVER['BACKUP_EMAIL_RECIPIENT'] = 'backup.box@example.com';

        $this->artisan('backup:run-and-email')->assertSuccessful();
        Mail::assertQueued(BackupArchiveMail::class, function ($mail) {
            $this->assertTrue($mail->attached);
            $this->assertCount(1, $mail->attachments());

            return true;
        });
        $this->assertDatabaseHas('backup_runs', ['email_attached' => true]);

        // Over the limit: status mail without attachment or filename disclosure.
        Mail::fake();
        putenv('BACKUP_EMAIL_MAX_MB=0');
        $_ENV['BACKUP_EMAIL_MAX_MB'] = '0';
        $_SERVER['BACKUP_EMAIL_MAX_MB'] = '0';
        $this->artisan('backup:run-and-email')->assertSuccessful();
        Mail::assertQueued(BackupArchiveMail::class, function ($mail) {
            $this->assertFalse($mail->attached);
            $this->assertSame([], $mail->attachments());
            $rendered = $mail->render();
            $this->assertStringNotContainsString('.zip', $rendered);
            $this->assertStringNotContainsString('C:', $rendered);
            $this->assertStringNotContainsString('/storage/', $rendered);

            return true;
        });

        // Blank recipient: backup completes, nothing queued.
        Mail::fake();
        putenv('BACKUP_EMAIL_RECIPIENT=');
        $_ENV['BACKUP_EMAIL_RECIPIENT'] = '';
        $_SERVER['BACKUP_EMAIL_RECIPIENT'] = '';
        $this->artisan('backup:run-and-email')->assertSuccessful();
        Mail::assertNothingQueued();
        $this->assertDatabaseHas('backup_runs', ['email_attached' => false]);
    }

    public function test_admin_backup_page_and_manual_run(): void
    {
        $admin = $this->admin();
        BackupRun::factory()->create(['status' => 'success']);

        $this->get(route('dhp.admin.backups.index'))->assertRedirect(route('login'));

        foreach (['citizen', 'issuer', 'verifier'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'is_active' => true]))
                ->get(route('dhp.admin.backups.index'))->assertForbidden();
        }

        $page = $this->actingAs($admin)->get(route('dhp.admin.backups.index'))->assertOk();
        $page->assertSee('Run Backup Now');
        $page->assertDontSee('.zip');
        $page->assertDontSee('Download');

        // Manual run is POST + CSRF, audits the request, queues the job.
        $this->actingAs($admin)->get(route('dhp.admin.backups.index'))->assertOk();
        $this->actingAs($admin)->post(route('dhp.admin.backups.run'))
            ->assertRedirect()->assertSessionHas('success', 'Backup requested. Check backup status later.');
        $this->assertDatabaseHas('audit_logs', ['action' => 'backup_run_requested']);
    }

    public function test_restore_rejects_bad_input_and_production_without_force(): void
    {
        $this->artisan('backup:restore', ['file' => '../evil.zip'])->assertFailed();
        $this->artisan('backup:restore', ['file' => 'C:\\temp\\x.zip'])->assertFailed();
        $this->artisan('backup:restore', ['file' => 'https://evil/x.zip'])->assertFailed();
        $this->assertDatabaseMissing('audit_logs', ['action' => 'backup_restore_started']);
        $this->assertDirectoryDoesNotExist(storage_path('app/backup-restore-temp'));

        $this->artisan('backup:run-and-email')->assertSuccessful();
        $zip = basename((string) $this->newestZip());
        $this->app['env'] = 'production';
        $this->artisan('backup:restore', ['file' => $zip])->assertFailed();
        $this->app['env'] = 'testing';
    }

    public function test_restore_roundtrip_recovers_data_and_cleans_up(): void
    {
        // Restore runs mysql import in a separate process, which cannot take
        // part in the test transaction (metadata locks) and cannot see its
        // uncommitted rows: restore into an isolated probe database instead,
        // exactly like restoring onto a new server. The known record is a
        // seeded demo patient, committed before tests start.
        $probe = 'health_passport_restore_probe';
        $original = config('database.connections.mysql.database');

        $this->assertNotNull(\App\Models\Patient::query()->where('national_id', 'KT7Y4M21')->first());
        $this->artisan('backup:run-and-email')->assertSuccessful();
        $zip = basename((string) $this->newestZip());
        $this->assertNotEmpty($zip);

        \Illuminate\Support\Facades\DB::statement("CREATE DATABASE IF NOT EXISTS `{$probe}`");

        try {
            config()->set('database.connections.mysql.database', $probe);
            \Illuminate\Support\Facades\DB::purge('mysql');

            $this->artisan('backup:restore', ['file' => $zip, '--force' => true])->assertSuccessful();

            $this->assertNotNull(\App\Models\Patient::query()->where('national_id', 'KT7Y4M21')->first());
            $this->assertDirectoryDoesNotExist(storage_path('app/backup-restore-temp'));
            // The started-audit cannot persist on an empty target: it fires
            // before the import creates the tables. The completed audit proves
            // the trail once tables exist.
            $this->assertDatabaseHas('audit_logs', ['action' => 'backup_restore_completed']);
        } finally {
            config()->set('database.connections.mysql.database', $original);
            \Illuminate\Support\Facades\DB::purge('mysql');
            \Illuminate\Support\Facades\DB::statement("DROP DATABASE IF EXISTS `{$probe}`");
        }
    }

    public function test_restore_failure_cleans_up_with_safe_audit(): void
    {
        $this->artisan('backup:run-and-email')->assertSuccessful();
        $zip = basename((string) $this->newestZip());

        $this->setBackupPassword('wrong-password');
        $this->artisan('backup:restore', ['file' => $zip, '--force' => true])->assertFailed();

        $this->assertDirectoryDoesNotExist(storage_path('app/backup-restore-temp'));
        $failed = AuditLog::query()->where('action', 'backup_restore_failed')->firstOrFail();
        $this->assertSame(['stage' => 'archive_extract'], $failed->details);
    }

    public function test_backup_schedule_entries_exist(): void
    {
        // Asserted on the schedule source: the Schedule singleton is only
        // populated when the console kernel boots, which HTTP tests do not do.
        $source = file_get_contents(base_path('routes/console.php'));
        $this->assertStringContainsString("Schedule::command('backup:run-and-email')->dailyAt('02:00')", $source);
        $this->assertStringContainsString("Schedule::command('backup:clean')->dailyAt('02:30')", $source);
        $this->assertStringContainsString("Schedule::command('backup:monitor')->dailyAt('03:00')", $source);
        $this->assertStringContainsString('withoutOverlapping', $source);
    }
}
