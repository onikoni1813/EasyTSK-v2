<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Release offerwall pending_balance holds into main_balance once their 24h hold expires
Schedule::command('offerwall:release-pending')->hourly();

// Clean up leftover proof screenshot files 24 hours after they are reviewed
Schedule::command('proofs:cleanup-screenshots')->hourly();

// Passively regenerate +20 health per day (capped at 100) for every user
Schedule::command('health:regenerate-daily')->dailyAt('00:05');

// Automatically distribute rewards for expired referral contests every 5 minutes
Schedule::command('referral-contest:distribute')->everyFiveMinutes();
