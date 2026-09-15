<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\LandingSlide;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutPrompts;

class LandingSlideshowTest extends TestCase
{
    use RefreshDatabaseWithoutPrompts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    protected function userWithRole(string $role): User
    {
        $facility = Facility::factory()->create();
        $user = User::factory()->create(['facility_id' => $facility->id, 'status' => 'active']);
        $user->assignRole($role);

        return $user;
    }

    public function test_landing_shows_default_slideshow(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('hero-slideshow', false);
        $response->assertSee('images/slide-banner2.jpg', false);
        $response->assertSee('images/phamarcy.jpg', false);
        $response->assertSee('data-interval="8"', false);
    }

    public function test_main_admin_can_upload_slide_and_set_interval(): void
    {
        Storage::fake('public');
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->get('/admin/landing-slides')->assertStatus(200);

        $this->actingAs($admin)->post(route('admin.landing-slides.store'), [
            'image' => new \Illuminate\Http\UploadedFile(
                storage_path('app/public/facility-logos/4C5WrtdNssncYWBvBiaiq9poMvZGO55lfY4wOcIX.jpg'),
                'ward.jpg',
                'image/jpeg',
                null,
                true
            ),
            'caption' => 'Ward block',
        ])->assertRedirect();

        $this->assertDatabaseHas('landing_slides', ['caption' => 'Ward block', 'is_active' => true]);
        Storage::disk('public')->assertExists(LandingSlide::first()->image_path);

        $this->actingAs($admin)->post(route('admin.landing-slides.interval'), [
            'landing_slide_interval' => 10,
        ])->assertRedirect();
        $this->assertSame(10, (int) Setting::get('landing_slide_interval'));

        // Landing now shows the uploaded slide with the admin-set interval.
        auth()->logout();
        $this->get('/')->assertStatus(200)->assertSee('data-interval="10"', false)->assertSee('Ward block', false);
    }

    public function test_facility_admin_cannot_manage_slides(): void
    {
        $admin = $this->userWithRole('facility_admin');

        $this->actingAs($admin)->get('/admin/landing-slides')->assertForbidden();
        $this->actingAs($admin)->post(route('admin.landing-slides.interval'), [
            'landing_slide_interval' => 10,
        ])->assertForbidden();
    }
}
