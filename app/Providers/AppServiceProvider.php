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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Auto-sync Vite production assets to public_html in split cPanel setup
        if (app()->environment('production')) {
            $publicHtml = dirname(base_path()) . '/public_html';
            $coreBuild = public_path('build');
            $publicHtmlBuild = $publicHtml . '/build';

            if (is_dir($publicHtml) && is_dir($coreBuild)) {
                if (!file_exists($publicHtmlBuild) && function_exists('symlink')) {
                    @symlink($coreBuild, $publicHtmlBuild);
                }

                $coreManifest = $coreBuild . '/manifest.json';
                $pubManifest = $publicHtmlBuild . '/manifest.json';

                if (file_exists($coreManifest) && (!file_exists($pubManifest) || @filemtime($coreManifest) > @filemtime($pubManifest))) {
                    try {
                        \Illuminate\Support\Facades\File::copyDirectory($coreBuild, $publicHtmlBuild);
                    } catch (\Throwable $e) {
                        // Silent fail if permission issue
                    }
                }
            }
        }
    }
}
