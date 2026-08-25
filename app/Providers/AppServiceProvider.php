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
        // Auto-sync Vite production assets and root public files to public_html in split cPanel setup
        if (app()->environment('production')) {
            $publicHtml = dirname(base_path()) . '/public_html';
            $corePublic = public_path();
            $coreBuild = public_path('build');
            $publicHtmlBuild = $publicHtml . '/build';

            if (is_dir($publicHtml)) {
                // Sync root assets (favicon.svg, favicon.ico, manifest.json, sw.js)
                $syncFiles = ['favicon.svg', 'favicon.ico', 'manifest.json', 'sw.js'];
                foreach ($syncFiles as $file) {
                    $src = $corePublic . '/' . $file;
                    $dst = $publicHtml . '/' . $file;
                    if (file_exists($src) && (!file_exists($dst) || @filemtime($src) > @filemtime($dst))) {
                        @copy($src, $dst);
                    }
                }

                if (is_dir($coreBuild)) {
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
}
