<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\SitePage;
use App\Services\AnalyticsService;
use App\Services\SeoService;
use App\Services\SiteContext;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show(string $slug, Request $request, SiteContext $siteContext, SeoService $seoService, AnalyticsService $analyticsService)
    {
        $site = $siteContext->get();
        if (!$site) {
            abort(404);
        }

        $page = SitePage::where('slug', $slug)->where('is_published', true)->firstOrFail();
        $analyticsService->record($request);

        $seo = $seoService->getMetadata(null, null, $page);

        return view('frontend.page', compact('site', 'page', 'seo'));
    }
}
