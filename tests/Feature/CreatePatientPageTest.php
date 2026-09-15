<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatePatientPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_loads_for_admin(): void
    {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $response = $this->actingAs($user)->get('/patients/create');
        $response->assertStatus(200);
        $response->assertSee('Check for an existing record first');
    }

    public function test_child_patient_can_be_registered_with_existing_guardian(): void
    {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $guardian = Guardian::factory()->create(['relationship' => 'Mother']);

        $response = $this->actingAs($user)->post('/patients', [
            'national_id' => '',
            'first_name' => 'Chisomo',
            'last_name' => 'Phiri',
            'date_of_birth' => '2021-05-10',
            'gender' => 'F',
            'district' => 'Lilongwe',
            'is_child' => '1',
            'guardian_id' => $guardian->id,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('patients', [
            'first_name' => 'Chisomo',
            'last_name' => 'Phiri',
            'is_child' => true,
            'guardian_id' => $guardian->id,
            'national_id' => null,
        ]);
    }

    public function test_child_patient_can_be_registered_with_inline_new_guardian(): void
    {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->post('/patients', [
            'national_id' => '',
            'first_name' => 'Mwai',
            'last_name' => 'Banda',
            'date_of_birth' => '2020-01-15',
            'gender' => 'M',
            'district' => 'Lilongwe',
            'is_child' => '1',
            'guardian_id' => '',
            'guardian_first_name' => 'Lindiwe',
            'guardian_last_name' => 'Banda',
            'guardian_relationship' => 'Mother',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('guardians', [
            'first_name' => 'Lindiwe',
            'last_name' => 'Banda',
            'relationship' => 'Mother',
        ]);
        $this->assertDatabaseHas('patients', [
            'first_name' => 'Mwai',
            'last_name' => 'Banda',
            'is_child' => true,
        ]);
    }
}