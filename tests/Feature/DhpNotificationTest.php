<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Citizen;
use App\Models\Credential;
use App\Models\District;
use App\Models\Facility;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Notifications\Dhp\BackupFailedNotification;
use App\Notifications\Dhp\CitizenAccountCreatedNotification;
use App\Notifications\Dhp\CitizenPinResetNotification;
use App\Notifications\Dhp\CredentialExpiringSoonNotification;
use App\Notifications\Dhp\CredentialIssuedNotification;
use App\Notifications\Dhp\CredentialRevokedOrReplacedNotification;
use App\Notifications\Dhp\StaffAccountCreatedNotification;
use App\Services\DhpNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Phase 7: queued, privacy-safe notifications. Mail is never required
 * for care, and no health data ever enters an email or audit row.
 */
class DhpNotificationTest extends TestCase
{
    use RefreshDatabase;

    // Seed like the legacy suites so every execution order finds demo data.
    protected bool $seed = true;

    private function issuer(): User
    {
        District::query()->firstOrCreate(['name' => 'Blantyre'], ['region' => 'Southern']);
        $facility = Facility::factory()->create();

        return User::factory()->create(['role' => 'issuer', 'is_active' => true, 'facility_id' => $facility->id]);
    }

    private function confirm(User $issuer, Citizen $citizen): void
    {
        $this->actingAs($issuer)->post(route('dhp.issuer.citizens.confirm-identity'), [
            'citizen_id' => $citizen->id,
            'first_name' => $citizen->first_name,
            'last_name' => $citizen->last_name,
        ]);
    }

    public function test_issuance_queues_notification_only_with_eligible_email(): void
    {
        Notification::fake();
        $issuer = $this->issuer();

        // Linked email citizen: queued.
        $user = User::factory()->create(['role' => 'citizen', 'is_active' => true, 'email' => 'mailed@example.com']);
        $citizen = Citizen::factory()->create(['user_id' => $user->id, 'district' => 'Blantyre']);
        $this->confirm($issuer, $citizen);
        $this->actingAs($issuer)->post(route('dhp.issuer.credentials.store', $citizen), [
            'type' => 'vaccination', 'issue_date' => now()->toDateString(),
            'vaccine_name' => 'BCG', 'dose_number' => 1, 'administration_date' => now()->toDateString(),
        ])->assertRedirect();
        Notification::assertSentTo($user, CredentialIssuedNotification::class);

        // Non-smartphone citizen: flow succeeds, nothing queued.
        Notification::fake();
        $plain = Citizen::factory()->create(['district' => 'Blantyre', 'user_id' => null, 'email' => null]);
        $this->confirm($issuer, $plain);
        $this->actingAs($issuer)->post(route('dhp.issuer.credentials.store', $plain), [
            'type' => 'vaccination', 'issue_date' => now()->toDateString(),
            'vaccine_name' => 'BCG', 'dose_number' => 1, 'administration_date' => now()->toDateString(),
        ])->assertRedirect();
        Notification::assertNothingSent();
    }

    public function test_registration_queues_activation_with_safe_audit(): void
    {
        Notification::fake();
        $issuer = $this->issuer();

        $this->actingAs($issuer)->post(route('dhp.issuer.citizens.store'), [
            'first_name' => 'Mailed', 'last_name' => 'Citizen', 'sex' => 'female',
            'date_of_birth' => '1995-04-04', 'district' => 'Blantyre',
            'email' => 'new.citizen@example.com', 'create_account' => '1',
        ])->assertSessionHasNoErrors();

        Notification::assertSentTo(
            User::query()->where('email', 'new.citizen@example.com')->firstOrFail(),
            CitizenAccountCreatedNotification::class,
            function ($notification) {
                return str_contains($notification->resetUrl, '/reset-password/')
                    && $notification->firstName === 'Mailed';
            }
        );

        $audit = AuditLog::query()->where('action', 'citizen_account_activation_notification_queued')->firstOrFail();
        $this->assertSame(['notification' => 'citizen_account_created', 'queued' => true], $audit->details);
        $this->assertStringNotContainsString('reset-password', json_encode($audit->details));
    }

    public function test_staff_creation_queues_activation_with_safe_audit(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->post(route('dhp.admin.users.store'), [
            'name' => 'Staff Member', 'email' => 'staff.member@example.com', 'role' => 'verifier', 'is_active' => '1',
        ])->assertRedirect();

        Notification::assertSentTo(
            User::query()->where('email', 'staff.member@example.com')->firstOrFail(),
            StaffAccountCreatedNotification::class
        );
        $audit = AuditLog::query()->where('action', 'staff_account_activation_notification_queued')->firstOrFail();
        $this->assertSame(
            ['notification' => 'staff_account_created', 'queued' => true, 'role' => 'verifier'],
            $audit->details
        );
    }

    public function test_revocation_queues_notification_when_eligible(): void
    {
        Notification::fake();
        $issuer = $this->issuer();
        $user = User::factory()->create(['role' => 'citizen', 'is_active' => true, 'email' => 'revoke.me@example.com']);
        $citizen = Citizen::factory()->create(['user_id' => $user->id, 'district' => 'Blantyre']);
        $this->confirm($issuer, $citizen);
        $this->actingAs($issuer)->post(route('dhp.issuer.credentials.store', $citizen), [
            'type' => 'vaccination', 'issue_date' => now()->toDateString(),
            'vaccine_name' => 'BCG', 'dose_number' => 1, 'administration_date' => now()->toDateString(),
        ]);
        $credential = Credential::query()->where('citizen_id', $citizen->id)->firstOrFail();

        $this->confirm($issuer, $citizen);
        $this->actingAs($issuer)->post(route('dhp.issuer.credentials.revoke', $credential), ['reason' => 'duplicate'])
            ->assertRedirect();

        Notification::assertSentTo($user, CredentialRevokedOrReplacedNotification::class);
    }

    public function test_notification_content_excludes_sensitive_data(): void
    {
        $user = User::factory()->make(['email' => 'reader@example.com']);
        $banned = ['national_id', 'NATIONAL', '1990-01-15', '+265', ' village', 'District ',
            'Positive', 'Malaria', 'BCG', 'BATCH', 'Dose 1', 'SECRET-PIN', 'secret-password',
            'qr_token', 'MW-DHP-2026-000001', 'password_reset_tokens'];

        $samples = [
            new CitizenAccountCreatedNotification('Mphatso', 'http://localhost/reset-password/TOKEN123?email=r@example.com'),
            new StaffAccountCreatedNotification('Mphatso', 'Issuer', 'http://localhost/reset-password/TOKEN123?email=r@example.com'),
            new CredentialIssuedNotification('Mphatso', 'Vaccination', 'MW-CRED-2026-000001', '1 Jan 2026', null, 'http://localhost/citizen/dashboard'),
            new CredentialRevokedOrReplacedNotification('Mphatso', 'MW-CRED-2026-000001', 'Revoked', 'duplicate', 'http://localhost/citizen/dashboard'),
            new CitizenPinResetNotification('Mphatso', 'http://localhost/citizen/dashboard'),
            new CredentialExpiringSoonNotification('Mphatso', 'Laboratory test', 'MW-CRED-2026-000002', '8 Feb 2026', 'http://localhost/citizen/dashboard'),
            new BackupFailedNotification('Mphatso', '1 Jan 2026 02:00', 'REF-1'),
        ];

        foreach ($samples as $notification) {
            $this->assertInstanceOf(ShouldQueue::class, $notification);
            $this->assertSame(['mail'], $notification->via($user));

            $mail = $notification->toMail($user);
            $text = json_encode([$mail->subject, $mail->greeting, $mail->introLines, $mail->outroLines, $mail->actionText, $mail->actionUrl]);

            $this->assertStringContainsString('Digital Health Passport', $text);
            foreach ($banned as $needle) {
                $this->assertStringNotContainsString($needle, $text, get_class($notification)." leaks {$needle}");
            }
        }

        // Reset URL uses the broker route, and the token never stands alone.
        $this->assertStringContainsString('/reset-password/', (new CitizenAccountCreatedNotification('A', 'http://x/reset-password/TOK?email=a@b.c'))->resetUrl);
    }

    public function test_password_reset_token_roundtrip_works(): void
    {
        $user = User::factory()->create(['email' => 'reset.me@example.com', 'password' => Hash::make('old-password-1')]);

        $token = Password::createToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => 'reset.me@example.com',
            'password' => 'new-password-2',
            'password_confirmation' => 'new-password-2',
        ])->assertRedirect(route('login'))->assertSessionHasNoErrors();

        $this->post(route('login.store'), ['email' => 'reset.me@example.com', 'password' => 'new-password-2'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_notify_expiring_selects_skips_and_deduplicates(): void
    {
        Notification::fake();

        $eligibleUser = User::factory()->create(['role' => 'citizen', 'is_active' => true, 'email' => 'expiring@example.com']);
        $eligibleCitizen = Citizen::factory()->create(['user_id' => $eligibleUser->id]);
        $due = Credential::factory()->labTest()->create([
            'citizen_id' => $eligibleCitizen->id, 'expiry_date' => today()->addDays(7)->toDateString(),
        ]);

        $noEmailCitizen = Citizen::factory()->create(['user_id' => null, 'email' => null]);
        Credential::factory()->labTest()->create([
            'citizen_id' => $noEmailCitizen->id, 'expiry_date' => today()->addDays(7)->toDateString(),
        ]);
        Credential::factory()->revoked()->create(['expiry_date' => today()->addDays(7)->toDateString()]);
        Credential::factory()->labTest()->create(['citizen_id' => $eligibleCitizen->id, 'expiry_date' => today()->addDays(6)->toDateString()]);
        Credential::factory()->labTest()->create(['citizen_id' => $eligibleCitizen->id, 'expiry_date' => today()->addDays(8)->toDateString()]);

        $this->artisan('credentials:notify-expiring')->assertSuccessful();
        Notification::assertSentTo($eligibleUser, CredentialExpiringSoonNotification::class);
        $this->assertDatabaseHas('notification_deliveries', [
            'notification_type' => 'credential_expiring_soon', 'related_type' => 'credential',
            'related_id' => $due->id, 'status' => 'queued',
        ]);
        $completed = AuditLog::query()->where('action', 'credentials_expiry_notification_job_completed')->firstOrFail();
        $this->assertSame(['queued_count' => 1, 'skipped_no_email_count' => 1], $completed->details);

        // Rerun queues nothing new.
        Notification::fake();
        $this->artisan('credentials:notify-expiring')->assertSuccessful();
        Notification::assertNothingSent();
        $again = AuditLog::query()->where('action', 'credentials_expiry_notification_job_completed')->latest('id')->firstOrFail();
        $this->assertSame(['queued_count' => 0, 'skipped_no_email_count' => 1], $again->details);

        // Uniqueness constraint holds under repeated attempts.
        $this->expectException(QueryException::class);
        NotificationDelivery::create([
            'notification_type' => 'credential_expiring_soon', 'related_type' => 'credential',
            'related_id' => $due->id, 'status' => 'queued', 'queued_at' => now(),
        ]);
    }

    public function test_dispatch_failure_writes_only_safe_audit(): void
    {
        $actor = User::factory()->create(['role' => 'issuer', 'is_active' => true]);
        $failing = new class extends User
        {
            public function notify($instance): void
            {
                throw new \RuntimeException('mail transport down');
            }
        };
        $failing->email = 'fails@example.com';
        $failing->name = 'Fails Example';
        $failing->role = 'issuer';

        $result = DhpNotificationService::queueStaffAccountCreated($failing, $actor);

        $this->assertFalse($result);
        $failed = AuditLog::query()->where('action', 'notification_queue_failed')->firstOrFail();
        $this->assertSame(['notification' => 'staff_account_created'], $failed->details);
    }
}
