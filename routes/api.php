<?php

use App\Http\Controllers\CronController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Optional HTTP cron endpoints
|--------------------------------------------------------------------------
|
| For hosts without a system scheduler. The CRON_SECRET must be sent in the
| Authorization header, and every request is rate limited. When the secret is
| empty the endpoints refuse every request. Servers with cron should use
| `php artisan schedule:run` and ignore these routes.
|
*/

Route::middleware('throttle:api')->group(function () {
    Route::get('/cron/reminders', [CronController::class, 'reminders'])->name('cron.reminders');
    Route::get('/cron/maintenance', [CronController::class, 'maintenance'])->name('cron.maintenance');
});
