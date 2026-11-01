<?php

use App\Http\Controllers\CronController;
use Illuminate\Support\Facades\Route;

/*
| Cron endpoints called by Vercel. Vercel sends the CRON_SECRET in the
| Authorization header, so the controller rejects every other caller.
*/

Route::get('/cron/reminders', [CronController::class, 'reminders'])->name('cron.reminders');
Route::get('/cron/maintenance', [CronController::class, 'maintenance'])->name('cron.maintenance');
