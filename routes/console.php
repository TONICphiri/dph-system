<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process the national sync queue every minute
Schedule::command('sync:process')->everyMinute();

// Back up the database every night at 02:00 and notify admins of the result.
// withoutOverlapping() prevents a slow backup from double-running if the
// scheduler ticks again before the previous run finishes.
Schedule::command('backup:run --keep-days=14')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground();
