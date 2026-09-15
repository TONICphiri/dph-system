<?php

namespace App\Providers;

use App\Listeners\LogFailedLogin;
use App\Models\Facility;
use App\Models\Setting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Failed::class, [LogFailedLogin::class, 'handleFailed']);
        Event::listen(Lockout::class, [LogFailedLogin::class, 'handleLockout']);

        // Ensure generated URLs (password reset links, signed verification links)
        // are always https:// in production, even behind a load balancer/proxy
        // that terminates TLS before it reaches PHP.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Public login page shows the display facility's profile
        // (hospital name, contact details, services, departments),
        // maintained by the Facility Admin via system settings.
        View::composer('auth.login', function ($view) {
            $view->with('displayFacility', $this->displayFacility());
        });

        // Public landing hero shows ONE slide image at a time, rotating
        // after the admin-set interval. Managed by the main (national)
        // admin at /admin/landing-slides — never by the facility admin.
        View::composer('landing', function ($view) {
            $view->with('landingSlides', $this->landingSlides());
            $view->with('landingSlideInterval', $this->landingSlideInterval());
            $view->with('displayFacility', $this->displayFacility());
        });

        // Offline banner data: records waiting to upload once the
        // internet is restored. Cached 60s so it costs almost nothing.
        View::composer('layouts.app', function ($view) {
            try {
                $unsynced = \Illuminate\Support\Facades\Cache::remember(
                    'sync.unsynced_count',
                    60,
                    fn () => \App\Models\SyncQueue::dueForSync()->count()
                );
            } catch (\Throwable) {
                $unsynced = 0;
            }

            $view->with('unsyncedCount', $unsynced);
            $view->with('syncEndpointOn', (bool) config('services.sync.endpoint'));
        });
    }

    /**
     * Facility shown on the public login page: the National Admin's
     * configured display facility, else the first active facility.
     * Null-safe: returns null when tables are unavailable.
     */
    protected function displayFacility(): ?Facility
    {
        try {
            if (!Schema::hasTable('facilities')) {
                return null;
            }

            if (Schema::hasTable('settings') && ($id = Setting::get('display_facility_id'))) {
                $facility = Facility::find($id);

                if ($facility) {
                    return $facility;
                }
            }

            return Facility::where('status', 'active')->orderBy('id')->first();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Active landing slides in admin-set order, with the two bundled
     * illustrations as fallback before the admin uploads any images.
     */
    protected function landingSlides(): array
    {
        try {
            if (!Schema::hasTable('landing_slides')) {
                return $this->defaultLandingSlides();
            }

            $slides = \App\Models\LandingSlide::where('is_active', true)
                ->orderBy('sort_order')->orderBy('id')->get();

            if ($slides->isEmpty()) {
                return $this->defaultLandingSlides();
            }

            return $slides->map(fn ($s) => [
                'src' => $s->image_url,
                'caption' => $s->caption,
            ])->all();
        } catch (\Throwable) {
            return $this->defaultLandingSlides();
        }
    }

    protected function defaultLandingSlides(): array
    {
        return [
            ['src' => asset('images/slide-banner2.jpg'), 'caption' => 'Digital health passport in use'],
            ['src' => asset('images/phamarcy.jpg'), 'caption' => 'Pharmacy dispensing'],
        ];
    }

    /** Seconds each slide shows, set by the main admin (default 8). */
    protected function landingSlideInterval(): int
    {
        try {
            return max(2, min(60, (int) Setting::get('landing_slide_interval', 8)));
        } catch (\Throwable) {
            return 8;
        }
    }
}
