<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Delete Sanctum tokens that expired more than 24 hours ago (no-op while SANCTUM_EXPIRATION is unset).
// Run by the chart's scheduler container (`php artisan schedule:work`).
Schedule::command('sanctum:prune-expired --hours=24')->daily();

Schedule::command('votes:prune-ips')->daily();
