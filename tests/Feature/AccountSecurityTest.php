<?php

namespace Tests\Feature;

use App\Enums\FacilityStatus;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_inactive_account_cannot_sign_in(): void
    {
        $user = User::query()->where('email', 'healthworker@healthpassport.mw')->firstOrFail();
        $user->update([
            'password' => 'TestPassword123!',
            'status' => UserStatus::Inactive,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'TestPassword123!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_at_inactive_facility_cannot_sign_in(): void
    {
        $user = User::query()->where('email', 'healthworker@healthpassport.mw')->firstOrFail();
        $user->update(['password' => 'TestPassword123!']);
        $user->facility->update(['status' => FacilityStatus::Inactive]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'TestPassword123!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_deactivated_account_session_is_invalidated_on_next_request(): void
    {
        $user = User::query()->where('email', 'healthworker@healthpassport.mw')->firstOrFail();
        $this->actingAs($user);
        $user->update(['status' => UserStatus::Inactive]);

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_session_at_deactivated_facility_is_invalidated_on_next_request(): void
    {
        $user = User::query()->where('email', 'healthworker@healthpassport.mw')->firstOrFail();
        $this->actingAs($user);
        $user->facility->update(['status' => FacilityStatus::Inactive]);

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_authenticated_responses_are_not_cacheable(): void
    {
        $user = User::query()->where('email', 'healthworker@healthpassport.mw')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertIsString($cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $response->assertHeader('Pragma', 'no-cache')->assertHeader('Expires', '0');
    }
}
