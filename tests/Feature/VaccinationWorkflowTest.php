<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\Vaccine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VaccinationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_the_same_vaccine_cannot_be_recorded_twice_for_a_holder_on_the_same_date(): void
    {
        $worker = User::query()->where('email', 'healthworker@healthpassport.mw')->firstOrFail();
        $patient = Patient::query()->where('national_id', 'PL2Q8X55')->firstOrFail();
        $vaccine = Vaccine::query()->where('is_active', true)->where('total_doses', '>', 1)->firstOrFail();
        $date = today()->toDateString();

        $this->actingAs($worker)
            ->post(route('patients.open'), ['code' => 'DHP:'.$patient->qr_token])
            ->assertRedirect(route('patients.show', $patient));

        $payload = ['vaccine_id' => $vaccine->id, 'administered_on' => $date];

        $this->post(route('vaccinations.store', $patient), $payload)
            ->assertRedirect(route('patients.show', $patient))
            ->assertSessionHasNoErrors();

        $this->from(route('patients.show', $patient))
            ->post(route('vaccinations.store', $patient), $payload)
            ->assertRedirect(route('patients.show', $patient))
            ->assertSessionHas('error');

        $doses = Vaccination::query()
            ->where('patient_id', $patient->id)
            ->where('vaccine_id', $vaccine->id)
            ->get();

        $this->assertCount(1, $doses);
        $this->assertSame(1, $doses->first()->dose_number);
    }

    public function test_a_later_dose_on_another_date_is_still_allowed(): void
    {
        $worker = User::query()->where('email', 'healthworker@healthpassport.mw')->firstOrFail();
        $patient = Patient::query()->where('national_id', 'PL2Q8X55')->firstOrFail();
        $vaccine = Vaccine::query()->where('is_active', true)->where('total_doses', '>', 1)->firstOrFail();
        $date = today();

        $this->actingAs($worker)
            ->post(route('patients.open'), ['code' => 'DHP:'.$patient->qr_token]);

        foreach ([$date, $date->copy()->addDay()] as $doseDate) {
            $this->travelTo($doseDate);
            $this->post(route('patients.open'), ['code' => 'DHP:'.$patient->qr_token])
                ->assertRedirect(route('patients.show', $patient));

            $this->post(route('vaccinations.store', $patient), [
                'vaccine_id' => $vaccine->id,
                'administered_on' => $doseDate->toDateString(),
            ])->assertRedirect(route('patients.show', $patient));
        }

        $doses = Vaccination::query()
            ->where('patient_id', $patient->id)
            ->where('vaccine_id', $vaccine->id)
            ->orderBy('dose_number')
            ->get();

        $this->assertCount(2, $doses);
        $this->assertSame([1, 2], $doses->pluck('dose_number')->all());
        $this->assertGreaterThanOrEqual(1, Reminder::query()
            ->where('patient_id', $patient->id)
            ->where('source_id', $vaccine->id)
            ->count());
    }
}
