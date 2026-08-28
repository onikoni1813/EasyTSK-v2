<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Site;
use App\Services\AnalyticsService;
use App\Services\SeoService;
use App\Services\SiteContext;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request, SiteContext $siteContext, SeoService $seoService, AnalyticsService $analyticsService)
    {
        $site = $siteContext->get();
        if (!$site) {
            // Check if any sites exist
            $fallback = Site::where('is_active', true)->first();
            if ($fallback) {
                $siteContext->set($fallback);
                $site = $fallback;
            } else {
                return view('errors.no-site-configured');
            }
        }

        $analyticsService->record($request);

        $featuredPosts = Post::featured()->with(['author', 'categories'])->latest('published_at')->take(3)->get();
        $trendingPosts = Post::trending()->with('categories')->latest('published_at')->take(5)->get();
        
        $posts = Post::published()
            ->with(['author', 'categories'])
            ->latest('published_at')
            ->paginate(10)
            ->withQueryString();

        $categories = Category::whereHas('posts', function ($q) {
            $q->published();
        })->withCount(['posts' => function ($q) {
            $q->published();
        }])->orderBy('sort_order')->take(10)->get();

        $seo = $seoService->getMetadata();
        $schema = $seoService->generateSchema();

        return view('frontend.home', compact('site', 'featuredPosts', 'trendingPosts', 'posts', 'categories', 'seo', 'schema'));
    }
}
