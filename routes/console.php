<?php

use App\Http\Controllers\QueueController;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('queue:daily-reset', function () {
    $today = now()->toDateString();
    QueueController::resetPreviousDayQueues($today);
    $this->info("Daily queue reset complete for {$today}.");
})->purpose('Auto-complete stale queues from previous days and free their windows');

// Run at midnight every day.
Schedule::command('queue:daily-reset')->dailyAt('00:00');
