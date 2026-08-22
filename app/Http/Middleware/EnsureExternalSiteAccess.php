<?php

namespace App\Http\Middleware;

use App\Services\SiteContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureExternalSiteAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (SiteContext::isExternal()) {
            $adminPath = env('ADMIN_PATH', 'admin');
            $path = trim($request->getPathInfo(), '/');

            $internalRoutes = [
                $adminPath,
                'dashboard',
                'tasks',
                'tasks-history',
                'withdraw',
                'withdraw-history',
                'reffer',
                'referrals',
                'referrals-history',
                'referral-contest',
                'profile',
                'settings',
                'campaigns',
                'campaigns-history',
                'login',
                'register',
                'recover-account',
            ];

            $internalPrefixes = [
                $adminPath . '/',
                'tasks/',
                'withdraw/',
                'referrals/',
                'wheel/',
                'promo/',
                'campaigns/',
                'password-tickets/',
            ];

            if (in_array($path, $internalRoutes)) {
                abort(404);
            }

            foreach ($internalPrefixes as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    abort(404);
                }
            }
        }

        return $next($request);
    }
}
