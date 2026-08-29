<?php

namespace App\Http\Middleware;

use App\Models\Site;
use App\Services\SiteContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenantSite
{
    public function __construct(protected SiteContext $siteContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $rawHost = $request->getHost();
        $host = strtolower(trim($rawHost));
        $site = null;

        // 1. Direct custom domain match (e.g., mycustomblog.com or blog1.easytsk.com)
        $site = Site::where('is_active', true)
            ->where(function ($q) use ($host) {
                $q->where('domain', $host)
                  ->orWhere('domain', 'www.' . $host)
                  ->orWhere('domain', preg_replace('/^www\./i', '', $host));
            })
            ->first();

        // 2. Subdomain extraction (e.g., blog1.easytsk.com, blog2.domain.xyz, blog3.localhost, blog4.test)
        if (!$site) {
            $parts = explode('.', $host);
            if (count($parts) >= 2) {
                $subdomainCandidate = $parts[0];
                $site = Site::where('is_active', true)
                    ->where(function ($q) use ($subdomainCandidate, $host) {
                        $q->where('subdomain', $subdomainCandidate)
                          ->orWhere('slug', $subdomainCandidate)
                          ->orWhere('domain', $host);
                    })
                    ->first();
            }
        }

        // 3. Query string override (useful for dev testing: ?site=blog1 or ?site=1 or ?site=crypto)
        if ($request->has('site')) {
            $param = $request->get('site');
            $querySite = is_numeric($param) 
                ? Site::where('id', $param)->where('is_active', true)->first()
                : Site::where('is_active', true)
                    ->where(function ($q) use ($param) {
                        $q->where('slug', $param)
                          ->orWhere('subdomain', $param)
                          ->orWhere('domain', $param);
                    })
                    ->first();
            
            if ($querySite) {
                $site = $querySite;
            }
        }

        // 4. Admin session override (when admin selects a site in Admin Panel)
        if (!$site && $request->is('admin*') && session()->has('admin_active_site_id')) {
            $site = Site::find(session('admin_active_site_id'));
        }

        // 5. Fallback for localhost root (picks first active site)
        if (!$site && ($host === 'localhost' || $host === '127.0.0.1')) {
            $site = Site::where('is_active', true)->orderBy('id')->first();
        }

        if ($site) {
            $this->siteContext->set($site);
            // Share current site with all Blade views automatically
            view()->share('currentSite', $site);
        }

        return $next($request);
    }
}

