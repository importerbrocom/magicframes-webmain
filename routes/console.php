<?php

use Illuminate\Support\Facades\Schedule;

// Needs ONE cron entry on the server:  * * * * * cd /path/to/site && php artisan schedule:run >> /dev/null 2>&1
if (config('backup.enabled')) {
    Schedule::command('site:backup')->dailyAt(config('backup.time'))->withoutOverlapping();
}
