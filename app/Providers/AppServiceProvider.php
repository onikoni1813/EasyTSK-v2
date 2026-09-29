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
        // Standalone PSR-4 autoloader for bundled WebPush packages on cPanel without composer CLI
        spl_autoload_register(function ($class) {
            $prefixes = [
                'Minishlink\\WebPush\\'     => base_path('vendor/minishlink/web-push/src/'),
                'Jose\\Component\\'         => base_path('vendor/web-token/jwt-library/'),
                'Base64Url\\'               => base_path('vendor/spomky-labs/base64url/src/'),
                'SpomkyLabs\\Pki\\'         => base_path('vendor/spomky-labs/pki-framework/src/'),
                'Http\\Discovery\\'         => base_path('vendor/php-http/discovery/src/'),
                'Http\\Client\\'            => base_path('vendor/php-http/httplug/src/'),
                'Http\\Promise\\'           => base_path('vendor/php-http/promise/src/'),
                'ParagonIE\\ConstantTime\\' => base_path('vendor/paragonie/constant_time_encoding/src/'),
                'Psr\\Http\\Client\\'       => base_path('vendor/psr/http-client/src/'),
            ];

            foreach ($prefixes as $prefix => $baseDir) {
                $len = strlen($prefix);
                if (strncmp($prefix, $class, $len) === 0) {
                    $relativeClass = substr($class, $len);
                    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
                    if (file_exists($file)) {
                        require_once $file;
                        return true;
                    }
                }
            }
            return false;
        });
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
                // Sync root assets (favicon.svg, favicon.ico, manifest.json, sw.js, icon-*.png)
                $syncFiles = ['favicon.svg', 'favicon.ico', 'manifest.json', 'sw.js', 'icon-192.png', 'icon-512.png'];
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
