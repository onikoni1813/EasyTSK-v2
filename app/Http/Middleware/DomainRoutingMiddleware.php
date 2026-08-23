<?php

namespace App\Http\Middleware;

use App\Models\Site;
use App\Models\SiteDomain;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DomainRoutingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // If site context is already set (e.g. via test or previous resolver), preserve it
        if (app()->has('current_site')) {
            return $next($request);
        }

        $rawHost = $request->header('Host') ?: $request->getHost();
        $host = strtolower(explode(':', $rawHost)[0]);

        $mainHost = parse_url(config('app.url'), PHP_URL_HOST);
        $mainHost = $mainHost ? strtolower($mainHost) : null;

        // If the current host is the primary application host, keep main platform context
        if ($mainHost && ($host === $mainHost || $host === 'www.' . $mainHost)) {
            return $next($request);
        }

        $siteDomain = SiteDomain::where('domain_name', $host)
            ->where('is_verified', true)
            ->with('site.siteType')
            ->first();

        $site = null;
        if ($siteDomain && $siteDomain->site && $siteDomain->site->status === 'active') {
            $site = $siteDomain->site;
        } else {
            // Check subdomain lookup
            $subdomain = explode('.', $host)[0] ?? null;
            if ($subdomain && !in_array($subdomain, ['www', 'localhost', '127', 'admin', 'easytsk'])) {
                $site = Site::where('subdomain', $subdomain)
                    ->where('status', 'active')
                    ->with('siteType')
                    ->first();
            }
        }

        if ($site) {
            app()->instance('current_site', $site);
            $request->attributes->set('current_site', $site);
        }

        return $next($request);
    }
}
