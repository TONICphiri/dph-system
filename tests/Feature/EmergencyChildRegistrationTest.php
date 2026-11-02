<?php

namespace Tests\Feature;

use App\Enums\IdentityStatus;
use App\Models\District;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A child who arrives without the mother can still be treated. The passport
 * is issued as provisional, and the mother is linked later by scanning her
 * card.
 */
class EmergencyChildRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function worker(): User
    {
        return User::query()->where('email', 'healthworker@healthpassport.mw')->firstOrFail();
    }

    private function mother(): Patient
    {
        return Patient::query()->where('national_id', 'KT7Y4M21')->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function emergencyChildPayload(array $overrides = []): array
    {
        return [
            'is_child' => 1,
            'emergency_child' => 1,
            'emergency_child_reason' => 'mother_absent',
            'guardian_name' => 'Grace Banda',
            'guardian_relationship' => 'Guardian',
            'guardian_phone' => '+265 991 000 111',
            'first_name' => 'Ruth',
            'last_name' => 'Banda',
            'date_of_birth' => now()->subMonths(2)->toDateString(),
            'dob_is_estimated' => 0,
            'sex' => 'female',
            'district_id' => District::query()->value('id'),
            'contacts' => [['full_name' => '', 'relationship' => '', 'phone' => '', 'physical_address' => '']],
            ...$overrides,
        ];
    }

    public function test_a_child_can_be_registered_as_an_emergency_when_the_mother_is_not_available(): void
    {
        $worker = $this->worker();

        $this->actingAs($worker)
            ->post(route('patients.store'), $this->emergencyChildPayload())
            ->assertRedirect();

        $child = Patient::query()->where('first_name', 'Ruth')->firstOrFail();

        $this->assertSame(IdentityStatus::Provisional, $child->identity_status);
        $this->assertNull($child->mother_id);
        $this->assertSame('Grace Banda', $child->guardian_name);
        $this->assertSame('+265 991 000 111', $child->guardian_phone);
        $this->assertSame($worker->id, $child->provisional_registered_by);
        $this->assertNotNull($child->provisional_registered_at);
        $this->assertFalse($child->dob_is_estimated);

        // The guardian is saved as the first emergency contact.
        $contact = $child->emergencyContacts()->firstOrFail();
        $this->assertSame('Grace Banda', $contact->full_name);
        $this->assertSame('+265 991 000 111', $contact->phone);
        $this->assertTrue($contact->is_primary);

        // No portal account is created for a provisional identity.
        $this->assertNull($child->portalAccount);

        // The provisional marker is visible on the profile, edit form and card.
        $this->get(route('patients.show', $child))->assertOk()->assertSee('Provisional identity');
        $this->get(route('patients.edit', $child))->assertOk()->assertSee('Why is the mother not available?');
        $this->get(route('patients.card', $child))->assertOk()->assertSee('Provisional identity');
    }

    public function test_an_emergency_child_requires_a_guardian_and_a_reason(): void
    {
        $this->actingAs($this->worker())
            ->post(route('patients.store'), $this->emergencyChildPayload([
                'guardian_name' => '',
                'guardian_phone' => '',
                'emergency_child_reason' => '',
            ]))
            ->assertSessionHasErrors(['guardian_name', 'guardian_phone', 'emergency_child_reason']);

        $this->assertDatabaseMissing('patients', ['first_name' => 'Ruth']);
    }

    public function test_unknown_sex_is_only_allowed_for_an_emergency_child(): void
    {
        $this->actingAs($this->worker())
            ->post(route('patients.store'), $this->emergencyChildPayload(['sex' => 'unknown']))
            ->assertRedirect();

        $child = Patient::query()->where('first_name', 'Ruth')->firstOrFail();
        $this->assertSame('unknown', $child->sex->value);

        // The same value is refused on a normal adult registration.
        $this->post(route('patients.store'), [
            'is_child' => 0,
            'national_id' => 'AA1B2C3D',
            'first_name' => 'John',
            'last_name' => 'Phiri',
            'date_of_birth' => '1990-01-01',
            'sex' => 'unknown',
            'district_id' => District::query()->value('id'),
            'contacts' => [['full_name' => 'Mary Phiri', 'relationship' => 'Sister', 'phone' => '+265 991 000 222']],
        ])->assertSessionHasErrors('sex');
    }

    public function test_scanning_the_mothers_passport_links_and_verifies_the_child(): void
    {
        $worker = $this->worker();
        $mother = $this->mother();

        $this->actingAs($worker)->post(route('patients.store'), $this->emergencyChildPayload());
        $child = Patient::query()->where('first_name', 'Ruth')->firstOrFail();

        $this->get(route('patients.scan', ['link_mother' => $child->id]))->assertOk()->assertSee($child->full_name);

        $this->post(route('patients.open'), [
            'code' => 'DHP:'.$mother->qr_token,
            'link_mother' => $child->id,
        ])->assertRedirect(route('patients.show', $child));

        $child->refresh();

        $this->assertSame($mother->id, $child->mother_id);
        $this->assertSame(IdentityStatus::Verified, $child->identity_status);
        $this->assertSame($worker->id, $child->identity_completed_by);
        $this->assertNotNull($child->identity_completed_at);
    }

    public function test_an_estimated_date_of_birth_keeps_the_record_provisional_after_linking(): void
    {
        $worker = $this->worker();
        $mother = $this->mother();

        $this->actingAs($worker)->post(route('patients.store'), $this->emergencyChildPayload(['dob_is_estimated' => 1]));
        $child = Patient::query()->where('first_name', 'Ruth')->firstOrFail();

        $this->post(route('patients.open'), [
            'code' => 'DHP:'.$mother->qr_token,
            'link_mother' => $child->id,
        ])->assertRedirect(route('patients.show', $child));

        $child->refresh();

        $this->assertSame($mother->id, $child->mother_id);
        $this->assertSame(IdentityStatus::Provisional, $child->identity_status);
        $this->assertTrue($child->dob_is_estimated);
    }
}
