<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Delete Sanctum tokens that expired more than 24 hours ago (no-op while SANCTUM_EXPIRATION is unset).
// Needs a scheduler (`php artisan schedule:run` every minute, e.g. a Kubernetes CronJob).
Schedule::command('sanctum:prune-expired --hours=24')->daily();
