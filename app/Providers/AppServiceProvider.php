<?php

namespace App\Providers;

use App\Services\SettingService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingService::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('components.pagination');

        // Five sign in attempts per minute for each email address and device.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(strtolower((string) $request->input('email')).'|'.$request->ip()));

        // The system name is edited on the Settings page, so every page reads
        // it from the settings table instead of a fixed value.
        // If the database cannot be reached, for example on an error page, the
        // application name from the environment file is used instead.
        View::composer('*', function ($view) {
            try {
                $name = app(SettingService::class)->systemName();
            } catch (Throwable) {
                $name = config('app.name');
            }

            $view->with('systemName', $name);
        });
    }
}
