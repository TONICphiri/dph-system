<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Opens every page for every role and checks that access rules hold and
 * that no page fails with a server error.
 */
class PageAccessTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /**
     * Pages each role must be able to open.
     *
     * @return array<string, array<int, string>>
     */
    private function allowedPages(): array
    {
        return [
            'admin@healthpassport.mw' => ['dashboard', 'admin.facilities.index', 'admin.districts.index', 'admin.facility-administrators.index', 'admin.settings.edit', 'admin.system-health', 'admin.vaccines.index', 'audit-log.index', 'campaigns.index'],
            'facility@healthpassport.mw' => ['dashboard', 'facility.staff.index', 'facility.wards.index', 'facility.bed-board', 'facility.schedules.index', 'facility.reports', 'appointments.index', 'admissions.index'],
            'clerk@healthpassport.mw' => ['dashboard', 'patients.index', 'patients.create', 'patients.scan', 'visits.queue'],
            'nurse@healthpassport.mw' => ['dashboard', 'visits.queue', 'admissions.index', 'facility.bed-board', 'patients.index'],
            'doctor@healthpassport.mw' => ['dashboard', 'visits.queue', 'admissions.index', 'patients.index', 'appointments.index'],
            'pharmacist@healthpassport.mw' => ['dashboard', 'pharmacy.index', 'medicines.index'],
            'patient@healthpassport.mw' => ['dashboard', 'portal.records', 'portal.appointments.index', 'portal.appointments.create', 'notifications.index'],
        ];
    }

    /**
     * Pages each role must not be able to open.
     *
     * @return array<string, array<int, string>>
     */
    private function forbiddenPages(): array
    {
        return [
            'admin@healthpassport.mw' => ['patients.index', 'pharmacy.index', 'portal.records'],
            'facility@healthpassport.mw' => ['admin.facilities.index', 'admin.settings.edit', 'pharmacy.index'],
            'clerk@healthpassport.mw' => ['admissions.index', 'pharmacy.index', 'facility.staff.index', 'admin.facilities.index'],
            'nurse@healthpassport.mw' => ['pharmacy.index', 'facility.staff.index', 'patients.create'],
            'doctor@healthpassport.mw' => ['pharmacy.index', 'facility.staff.index', 'admin.settings.edit'],
            'pharmacist@healthpassport.mw' => ['patients.index', 'visits.queue', 'admissions.index'],
            'patient@healthpassport.mw' => ['patients.index', 'visits.queue', 'pharmacy.index', 'admin.facilities.index'],
        ];
    }

    public function test_each_role_can_open_its_own_pages(): void
    {
        foreach ($this->allowedPages() as $email => $routes) {
            $user = User::query()->where('email', $email)->firstOrFail();

            foreach ($routes as $route) {
                $status = $this->actingAs($user)->get(route($route))->getStatusCode();
                $this->assertSame(200, $status, "{$email} should open {$route}.");
            }
        }
    }

    public function test_each_role_is_refused_pages_of_other_roles(): void
    {
        foreach ($this->forbiddenPages() as $email => $routes) {
            $user = User::query()->where('email', $email)->firstOrFail();

            foreach ($routes as $route) {
                $status = $this->actingAs($user)->get(route($route))->getStatusCode();
                $this->assertSame(403, $status, "{$email} should not open {$route}.");
            }
        }
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('patients.index'))->assertRedirect(route('login'));
    }

    public function test_no_page_without_parameters_fails_for_any_role(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => in_array('GET', $route->methods(), true)
                && $route->getName()
                && ! str_contains($route->uri(), '{')
                && ! str_starts_with($route->getName(), 'storage.')
                && ! in_array($route->getName(), ['logout', 'up'], true));

        foreach (RoleName::cases() as $role) {
            $user = User::query()->role($role->value)->firstOrFail();

            foreach ($routes as $route) {
                $status = $this->actingAs($user)->get('/'.ltrim($route->uri(), '/'))->getStatusCode();
                $this->assertLessThan(500, $status, "{$route->getName()} failed for {$role->label()} with status {$status}.");
            }
        }
    }
}
