<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule::command('inspire')->hourly();
Schedule::command('subscriptions:cancel-expired')->hourly();
Schedule::command('evenleads:cleanup-stuck-syncs')->everyFifteenMinutes();
Schedule::command('evenleads:run-automated-syncs')->everyFifteenMinutes();

// Account Warmup - Run hourly to process scheduled warmup activities
Schedule::command('warmup:run')->hourly();

// API Usage Cleanup - Run daily at 2 AM to clean up logs older than 90 days
Schedule::command('api-usage:cleanup')->dailyAt('02:00');
