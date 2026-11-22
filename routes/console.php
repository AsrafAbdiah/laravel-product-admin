<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| Run by `php artisan schedule:work` locally, or a single cron entry
| (`* * * * * php artisan schedule:run`) in production.
|
*/

// Monday morning stock summary for each shop's owners and admins.
Schedule::command('bandaros:weekly-summary')->weeklyOn(1, '08:00')->withoutOverlapping();

// Housekeeping.
Schedule::command('bandaros:prune-exports')->daily();
Schedule::command('model:prune')->daily();          // expired team invitations
Schedule::command('activitylog:clean')->daily();    // audit entries older than a year
