<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\DomainRoutingMiddleware::class,
            \App\Http\Middleware\EnsureExternalSiteAccess::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\CheckMaintenanceMode::class,
        ]);
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            'postback/*',
            'logout',
        ]);
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'not_banned' => \App\Http\Middleware\EnsureUserIsNotBanned::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

if (is_dir(dirname(__DIR__) . '/../public_html') && file_exists(dirname(__DIR__) . '/../public_html/index.php')) {
    $app->usePublicPath(realpath(dirname(__DIR__) . '/../public_html'));
}

return $app;
