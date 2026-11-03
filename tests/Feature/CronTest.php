<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * External schedulers call these endpoints, so they must stay closed to
 * anyone who does not know the shared secret.
 */
class CronTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        config(['health_passport.cron_secret' => 'test-cron-secret']);
    }

    public function test_cron_endpoints_reject_a_missing_or_wrong_secret(): void
    {
        $this->getJson('/api/cron/reminders')->assertStatus(401);
        $this->getJson('/api/cron/maintenance')->assertStatus(401);

        $this->withHeader('Authorization', 'Bearer wrong-secret')
            ->getJson('/api/cron/reminders')
            ->assertStatus(401);
    }

    public function test_cron_endpoints_are_closed_when_no_secret_is_configured(): void
    {
        config(['health_passport.cron_secret' => null]);

        $this->withHeader('Authorization', 'Bearer anything')
            ->getJson('/api/cron/reminders')
            ->assertStatus(401);
    }

    public function test_reminders_endpoint_runs_the_reminder_command(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('reminders:send')
            ->andReturn(0);
        Artisan::shouldReceive('output')->andReturn('2 reminders delivered.');

        $response = $this->withHeader('Authorization', 'Bearer test-cron-secret')
            ->getJson('/api/cron/reminders');

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertSame(0, $response->json('results.reminders:send.exit_code'));
    }

    public function test_maintenance_endpoint_runs_the_backup_and_separation_commands(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('backup:run', ['--scheduled' => true])
            ->andReturn(0);
        Artisan::shouldReceive('call')
            ->once()
            ->with('patients:separate-adults', [])
            ->andReturn(0);
        Artisan::shouldReceive('output')->andReturn('ok');

        $response = $this->withHeader('Authorization', 'Bearer test-cron-secret')
            ->getJson('/api/cron/maintenance');

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertSame(0, $response->json('results.backup:run.exit_code'));
        $this->assertSame(0, $response->json('results.patients:separate-adults.exit_code'));
    }

    public function test_a_failing_command_returns_an_error_status(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('reminders:send')
            ->andReturn(1);
        Artisan::shouldReceive('output')->andReturn('The reminder service is unavailable.');

        $this->withHeader('Authorization', 'Bearer test-cron-secret')
            ->getJson('/api/cron/reminders')
            ->assertStatus(500)
            ->assertJson(['ok' => false]);
    }
}
