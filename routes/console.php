<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:prune-operational-logs')->dailyAt('02:30')->withoutOverlapping();

if (config('capacity.monitor_enabled')) {
    Schedule::command('capacity:report --host --json')->hourly()->withoutOverlapping(10)
        ->appendOutputTo(storage_path('logs/capacity.log'));
}

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
