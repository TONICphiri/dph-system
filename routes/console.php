<?php

use Illuminate\Support\Facades\Schedule;

/*
| Scheduled tasks. On cPanel, add one cron job that runs every minute:
| php /home/USERNAME/health-passport/artisan schedule:run >> /dev/null 2>&1
*/

Schedule::command('reminders:send')->dailyAt('07:00')->withoutOverlapping();
Schedule::command('patients:separate-adults')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('credentials:mark-expired')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('credentials:notify-expiring')->dailyAt('01:15')->withoutOverlapping();
Schedule::command('backup:run-and-email')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('backup:clean')->dailyAt('02:30')->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('03:00')->withoutOverlapping();
