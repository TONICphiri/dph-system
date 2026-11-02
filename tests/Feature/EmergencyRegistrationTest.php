<?php

namespace Tests\Feature;

use App\Enums\IdentityStatus;
use App\Enums\Sex;
use App\Models\District;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A patient can be issued a passport during an emergency, even when the usual
 * identity details are not available. The record stays provisional until the
 * missing details are supplied.
 */
class EmergencyRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function worker(): User
    {
        return User::query()->where('email', 'healthworker@healthpassport.mw')->firstOrFail();
    }

    private function districtId(): int
    {
        return District::query()->firstOrFail()->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function emergencyAdult(array $overrides = []): array
    {
        return [
            'is_child' => '0',
            'emergency' => '1',
            'first_name' => 'Emergadult',
            'last_name' => 'Identitycase',
            'date_of_birth' => now()->subYears(30)->toDateString(),
            'dob_is_estimated' => '1',
            'sex' => 'unknown',
            'district_id' => $this->districtId(),
            'emergency_reason' => 'unconscious',
            'next_of_kin_name' => 'Mary Doe',
            'next_of_kin_relationship' => 'Sister',
            'next_of_kin_phone' => '+265 991 000 222',
            ...$overrides,
        ];
    }

    public function test_an_adult_can_be_registered_as_an_emergency_without_a_national_id(): void
    {
        $this->actingAs($this->worker())
            ->post(route('patients.store'), $this->emergencyAdult(['email' => 'emergency.adult@example.com', 'create_portal_account' => '1']))
            ->assertRedirect();

        $patient = Patient::query()->where('first_name', 'Emergadult')->firstOrFail();

        $this->assertSame(IdentityStatus::Provisional, $patient->identity_status);
        $this->assertNull($patient->national_id);
        $this->assertSame(Sex::Unknown, $patient->sex);
        $this->assertTrue($patient->dob_is_estimated);
        $this->assertSame('unconscious', $patient->provisional_reason);
        $this->assertSame('Mary Doe', $patient->next_of_kin_name);
        $this->assertSame($this->worker()->id, $patient->provisional_registered_by);
        $this->assertNotNull($patient->provisional_registered_at);

        // No portal account may be created until the identity is complete.
        $this->assertFalse($patient->portalAccount()->exists());

        // The next of kin becomes the first emergency contact.
        $this->assertDatabaseHas('emergency_contacts', [
            'patient_id' => $patient->id,
            'full_name' => 'Mary Doe',
            'phone' => '+265 991 000 222',
        ]);
    }

    public function test_a_normal_adult_still_needs_a_national_id_and_a_known_sex(): void
    {
        $this->actingAs($this->worker())
            ->post(route('patients.store'), [
                'is_child' => '0',
                'first_name' => 'Normal',
                'last_name' => 'Adult',
                'date_of_birth' => now()->subYears(25)->toDateString(),
                'sex' => 'unknown',
                'district_id' => $this->districtId(),
                'contacts' => [['full_name' => 'Contact Person', 'relationship' => 'Friend', 'phone' => '+265 991 000 333']],
            ])
            ->assertSessionHasErrors(['national_id', 'sex']);
    }

    public function test_an_emergency_registration_needs_a_reason_and_next_of_kin(): void
    {
        $payload = $this->emergencyAdult();
        unset($payload['emergency_reason'], $payload['next_of_kin_name'], $payload['next_of_kin_phone']);

        $this->actingAs($this->worker())
            ->post(route('patients.store'), $payload)
            ->assertSessionHasErrors(['emergency_reason', 'next_of_kin_name', 'next_of_kin_phone']);
    }

    public function test_a_child_can_be_registered_as_an_emergency_without_the_mother(): void
    {
        $this->actingAs($this->worker())
            ->post(route('patients.store'), [
                ...$this->emergencyAdult(),
                'is_child' => '1',
                'first_name' => 'Emergchild',
                'last_name' => 'Identitycase',
                'date_of_birth' => now()->subMonths(3)->toDateString(),
                'sex' => 'female',
                'dob_is_estimated' => '0',
                'emergency_reason' => 'mother_unavailable',
            ])
            ->assertRedirect();

        $child = Patient::query()->where('first_name', 'Emergchild')->firstOrFail();

        $this->assertNull($child->mother_id);
        $this->assertSame(IdentityStatus::Provisional, $child->identity_status);
        $this->assertSame('mother_unavailable', $child->provisional_reason);
        $this->assertTrue($child->isChild());
    }

    public function test_linking_the_mother_completes_a_child_emergency_identity(): void
    {
        $this->actingAs($this->worker())
            ->post(route('patients.store'), [
                ...$this->emergencyAdult(),
                'is_child' => '1',
                'first_name' => 'Emergchild',
                'last_name' => 'Identitycase',
                'date_of_birth' => now()->subMonths(3)->toDateString(),
                'sex' => 'female',
                'dob_is_estimated' => '0',
                'emergency_reason' => 'mother_unavailable',
            ]);

        $child = Patient::query()->where('first_name', 'Emergchild')->firstOrFail();
        $mother = Patient::query()->where('national_id', 'KT7Y4M21')->firstOrFail();

        $this->post(route('patients.open'), [
            'code' => 'DHP:'.$mother->qr_token,
            'link_mother' => $child->id,
        ])->assertRedirect();

        $this->assertSame($mother->id, $child->fresh()->mother_id);
        $this->assertSame(IdentityStatus::Verified, $child->fresh()->identity_status);
        $this->assertNotNull($child->fresh()->identity_completed_at);
    }

    public function test_completing_the_identity_of_an_emergency_adult_verifies_the_passport(): void
    {
        $this->actingAs($this->worker())
            ->post(route('patients.store'), $this->emergencyAdult());

        $patient = Patient::query()->where('first_name', 'Emergadult')->firstOrFail();

        $this->put(route('patients.update', $patient), [
            'national_id' => 'NM123456',
            'first_name' => 'Emergadult',
            'last_name' => 'Identitycase',
            'date_of_birth' => '1990-01-01',
            'dob_is_estimated' => '0',
            'sex' => 'male',
            'status' => 'active',
            'district_id' => $this->districtId(),
            'emergency_reason' => 'unconscious',
            'next_of_kin_name' => 'Mary Doe',
            'next_of_kin_relationship' => 'Sister',
            'next_of_kin_phone' => '+265 991 000 222',
        ])->assertRedirect();

        $patient->refresh();

        $this->assertSame('NM123456', $patient->national_id);
        $this->assertSame(IdentityStatus::Verified, $patient->identity_status);
        $this->assertFalse($patient->dob_is_estimated);
        $this->assertSame(Sex::Male, $patient->sex);
        $this->assertNotNull($patient->identity_completed_at);
        $this->assertSame($this->worker()->id, $patient->identity_completed_by);
    }

    public function test_a_normal_child_can_still_be_edited_without_relinking_the_mother(): void
    {
        $child = Patient::query()->whereNotNull('mother_id')->firstOrFail();

        $this->actingAs($this->worker())
            ->post(route('patients.open'), ['code' => 'DHP:'.$child->qr_token])
            ->assertRedirect(route('patients.show', $child));

        $this->put(route('patients.update', $child), [
            'first_name' => $child->first_name,
            'last_name' => $child->last_name,
            'date_of_birth' => $child->date_of_birth->toDateString(),
            'sex' => $child->sex->value,
            'status' => 'active',
            'district_id' => $child->district_id,
            'contacts' => [['full_name' => 'Contact Person', 'relationship' => 'Guardian', 'phone' => '+265 991 000 444']],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame($child->mother_id, $child->fresh()->mother_id);
    }

    public function test_the_passport_shows_the_provisional_identity(): void
    {
        $this->actingAs($this->worker())
            ->post(route('patients.store'), $this->emergencyAdult());

        $patient = Patient::query()->where('first_name', 'Emergadult')->firstOrFail();

        $this->get(route('patients.show', $patient))
            ->assertOk()
            ->assertSee('Provisional identity')
            ->assertSee('Patient is unconscious or unable to give details')
            ->assertSee('Mary Doe');
    }
}
