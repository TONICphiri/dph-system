<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Accounts created by an administrator start with a one time password and
 * must be shown the blocking change-password pop-up on their first sign in.
 */
class FirstLoginPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function userWithTemporaryPassword(string $email): User
    {
        $user = User::query()->where('email', $email)->firstOrFail();

        $user->forceFill([
            'password' => 'TempPass123',
            'must_change_password' => true,
        ])->save();

        return $user->fresh();
    }

    public function test_dashboard_shows_the_change_password_popup_for_staff_and_patients(): void
    {
        foreach (['clerk@healthpassport.mw', 'patient@healthpassport.mw'] as $email) {
            $user = $this->userWithTemporaryPassword($email);

            $response = $this->actingAs($user)->get(route('dashboard'));

            $response->assertOk("{$email} was redirected to ".$response->headers->get('Location'));

            $response
                ->assertSee('Change your password')
                ->assertSee('one-time password')
                ->assertSee('name="current_password"', false);
        }
    }

    public function test_other_pages_stay_blocked_until_the_password_is_changed(): void
    {
        $user = $this->userWithTemporaryPassword('clerk@healthpassport.mw');

        $this->actingAs($user)
            ->get(route('patients.scan'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($user)
            ->getJson(route('notifications.unread'))
            ->assertStatus(423);
    }

    public function test_changing_the_password_removes_the_popup_and_restores_access(): void
    {
        $user = $this->userWithTemporaryPassword('clerk@healthpassport.mw');

        $response = $this->actingAs($user)
            ->put(route('password.change.update'), [
                'current_password' => 'TempPass123',
                'password' => 'NewPass1234',
                'password_confirmation' => 'NewPass1234',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('NewPass1234', $user->password));

        $this->actingAs($user)->get(route('patients.scan'))->assertOk();
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertDontSee('one-time password');
    }
}
