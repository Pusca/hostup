<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pull OTA calendars into the master calendar every 15 minutes.
Schedule::command('hostup:sync-ical')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();
