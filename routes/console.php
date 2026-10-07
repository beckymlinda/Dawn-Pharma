<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly database backup (needs the cPanel cron job: php artisan schedule:run, every minute)
Schedule::command('backup:database')->dailyAt('23:30')->withoutOverlapping();
