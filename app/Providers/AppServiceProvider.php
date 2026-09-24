<?php

namespace App\Providers;

use App\Services\SettingService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        View::composer('*', function ($view) {
            $view->with('systemName', app(SettingService::class)->systemName());
        });
    }
}
