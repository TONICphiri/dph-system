<?php

namespace Tests\Feature;

use App\Models\Reminder;
use App\Models\User;
use App\Models\Vaccine;
use App\Notifications\ReminderScheduledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderPortalNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function holder(): User
    {
        return User::query()->where('email', 'patient@healthpassport.mw')->firstOrFail();
    }

    private function worker(): User
    {
        return User::query()->where('email', 'healthworker@healthpassport.mw')->firstOrFail();
    }

    private function openPassport(User $worker, User $holder): void
    {
        $this->actingAs($worker)
            ->post(route('patients.open'), ['code' => 'DHP:'.$holder->patient->qr_token])
            ->assertRedirect(route('patients.show', $holder->patient));
    }

    public function test_saving_a_reminder_adds_a_notification_to_the_patient_portal(): void
    {
        $holder = $this->holder();
        $existingNotifications = $holder->notifications()->pluck('id');
        $worker = $this->worker();
        $this->openPassport($worker, $holder);

        $this->post(route('reminders.store', $holder->patient), [
            'category' => 'follow_up',
            'title' => 'Clinic follow-up',
            'message' => 'Return for a review.',
            'due_on' => today()->addDays(3)->toDateString(),
        ])
            ->assertRedirect(route('patients.show', $holder->patient))
            ->assertSessionHas('success', 'The reminder has been set. A notification has been added to the patient portal.');

        $notification = $holder->notifications()
            ->where('type', ReminderScheduledNotification::class)
            ->whereNotIn('id', $existingNotifications)
            ->firstOrFail();

        $this->assertSame('Clinic follow-up', $notification->data['title']);
        $this->assertStringContainsString('Return for a review.', $notification->data['message']);

        $this->actingAs($holder)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Clinic follow-up')
            ->assertSee('Return for a review.');

        $this->get(route('portal.records'))
            ->assertOk()
            ->assertSee('Upcoming reminders')
            ->assertSee('Clinic follow-up');
    }

    public function test_recording_a_vaccine_with_a_next_dose_notifies_the_patient(): void
    {
        $holder = $this->holder();
        $existingNotifications = $holder->notifications()->pluck('id');
        $worker = $this->worker();
        $patient = $holder->patient;
        $vaccine = Vaccine::query()->create([
            'name' => 'Portal reminder test vaccine',
            'protects_against' => 'A test illness',
            'total_doses' => 2,
            'days_between_doses' => 28,
            'recommended_age_days' => 0,
            'is_active' => true,
        ]);
        $this->openPassport($worker, $holder);

        $this->post(route('vaccinations.store', $patient), [
            'vaccine_id' => $vaccine->id,
            'administered_on' => today()->toDateString(),
        ])->assertRedirect(route('patients.show', $patient));

        $notification = $holder->notifications()
            ->where('type', ReminderScheduledNotification::class)
            ->whereNotIn('id', $existingNotifications)
            ->firstOrFail();

        $this->assertSame("{$vaccine->name}, dose 2", $notification->data['title']);
        $this->assertStringContainsString('Due on', $notification->data['message']);

        $this->actingAs($holder)
            ->get(route('portal.records'))
            ->assertOk()
            ->assertSee('Upcoming reminders')
            ->assertSee($vaccine->name);
    }

    public function test_confidential_reminder_notice_does_not_expose_its_details(): void
    {
        $holder = $this->holder();
        $existingNotifications = $holder->notifications()->pluck('id');

        $reminder = Reminder::query()->create([
            'patient_id' => $holder->patient_id,
            'category' => 'medication',
            'title' => 'Confidential medication',
            'message' => 'Confidential clinical details.',
            'due_on' => today()->addDay()->toDateString(),
            'is_confidential' => true,
            'status' => 'active',
        ]);

        $notification = $holder->notifications()
            ->where('type', ReminderScheduledNotification::class)
            ->whereNotIn('id', $existingNotifications)
            ->firstOrFail();

        $this->assertSame('Health reminder added', $notification->data['title']);
        $this->assertSame('reminder', $notification->data['category']);
        $this->assertStringNotContainsString('Confidential medication', $notification->data['message']);
        $this->assertStringNotContainsString('Confidential clinical details', $notification->data['message']);
        $this->assertSame(
            route('portal.records', ['patient' => $reminder->patient_id]),
            $notification->data['url'],
        );
    }
}
