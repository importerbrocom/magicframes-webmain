<?php

use Illuminate\Support\Facades\Schedule;

// Needs ONE cron entry on the server:  * * * * * cd /path/to/site && php artisan schedule:run >> /dev/null 2>&1
if (config('backup.enabled')) {
    Schedule::command('site:backup')->dailyAt(config('backup.time'))->withoutOverlapping();
}

// Keep the home page Instagram feed fresh (does nothing until a token is saved in Site settings)
Schedule::command('instagram:sync')->hourly()->withoutOverlapping();
