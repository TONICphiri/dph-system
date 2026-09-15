<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutPrompts;

class OfflineSupportTest extends TestCase
{
    use RefreshDatabaseWithoutPrompts;

    public function test_offline_fallback_page_renders(): void
    {
        $this->get('/offline')->assertStatus(200)->assertSee('No connection to the server', false);
    }

    public function test_service_worker_and_manifest_exist(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('manifest.webmanifest'));

        $sw = file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString('/offline', $sw);
        // Pages are never cached — staff must never see stale patient data.
        $this->assertStringContainsString("req.method !== 'GET'", $sw);
    }

    public function test_app_layout_supports_offline(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $facility = Facility::factory()->create();
        $user = User::factory()->create(['facility_id' => $facility->id, 'status' => 'active']);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        // Service worker registration + browser offline banner.
        $response->assertSee("register('/sw.js')", false);
        $response->assertSee('browser-offline-banner', false);
        // Fonts must not block rendering when the internet is gone.
        $response->assertSee('media="print"', false);
    }

    public function test_login_page_hides_map_when_offline(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('mapOffline', false);
        $response->assertSee('navigator.onLine', false);
    }
}
