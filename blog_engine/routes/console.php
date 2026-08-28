<?php

use App\Models\Post;
use App\Services\AnalyticsService;
use Illuminate\Support\Facades\Schedule;

// Publish scheduled posts every minute
Schedule::call(function () {
    Post::withoutGlobalScopes()
        ->where('status', 'scheduled')
        ->whereNotNull('published_at')
        ->where('published_at', '<=', now())
        ->update(['status' => 'published']);
})->everyMinute()->name('publish-scheduled-posts');

// Aggregate daily analytics every hour
Schedule::call(function () {
    app(AnalyticsService::class)->aggregateDaily();
})->hourly()->name('aggregate-daily-analytics');
