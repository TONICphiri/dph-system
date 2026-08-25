<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DhpIdLookupTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    public function test_dhp_id_lookup_api_finds_existing_patient(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create(['dhp_id' => 'DHP-2026-00000001']);

        $response = $this->actingAs($user)->get('/api/patients/search-by-dhp-id?dhp_id=DHP-2026-00000001');

        $response->assertStatus(200)
            ->assertJsonPath('found', true)
            ->assertJsonPath('patient.dhp_id', 'DHP-2026-00000001')
            ->assertJsonPath('patient.id', $patient->id);
    }

    public function test_dhp_id_lookup_api_returns_404_for_missing_patient(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get('/api/patients/search-by-dhp-id?dhp_id=DHP-2026-99999999');

        $response->assertStatus(404)
            ->assertJsonPath('found', false);
    }

    public function test_dhp_id_lookup_api_accepts_scanned_qr_payload(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create(['dhp_id' => 'DHP-2026-00000003']);
        $payload = urlencode(json_encode(['dhp_id' => $patient->dhp_id]));

        $response = $this->actingAs($user)->get("/api/patients/search-by-dhp-id?dhp_id={$payload}");

        $response->assertStatus(200)
            ->assertJsonPath('found', true)
            ->assertJsonPath('patient.dhp_id', 'DHP-2026-00000003')
            ->assertJsonPath('patient.id', $patient->id);
    }

    public function test_index_page_has_dhp_lookup_box(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get('/patients');

        $response->assertStatus(200)
            ->assertSee('QR / DHP ID Lookup')
            ->assertSee('lookup-dhp-id')
            ->assertSee('Scan QR Code');
    }

    public function test_show_page_has_qr_code_link(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();

        $response = $this->actingAs($user)->get("/patients/{$patient->id}");

        $response->assertStatus(200)
            ->assertSee('View QR Code');
    }

    public function test_qr_code_page_renders_for_patient(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create(['dhp_id' => 'DHP-2026-00000002']);

        $response = $this->actingAs($user)->get("/patients/{$patient->id}/qr-code");

        $response->assertStatus(200)
            ->assertSee($patient->dhp_id)
            ->assertSee('Print QR Code');
    }
}
