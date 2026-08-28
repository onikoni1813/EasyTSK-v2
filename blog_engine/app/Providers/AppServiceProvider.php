<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\SiteContext::class, function () {
            return new \App\Services\SiteContext();
        });

        $this->app->singleton(\App\Services\AdEngine::class, function ($app) {
            return new \App\Services\AdEngine($app->make(\App\Services\SiteContext::class));
        });

        $this->app->singleton(\App\Services\SeoService::class, function ($app) {
            return new \App\Services\SeoService($app->make(\App\Services\SiteContext::class));
        });

        $this->app->singleton(\App\Services\AnalyticsService::class, function ($app) {
            return new \App\Services\AnalyticsService($app->make(\App\Services\SiteContext::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
