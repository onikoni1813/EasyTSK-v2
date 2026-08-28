<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Services\AnalyticsService;
use App\Services\SeoService;
use App\Services\SiteContext;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function show(string $slug, Request $request, SiteContext $siteContext, SeoService $seoService, AnalyticsService $analyticsService)
    {
        $site = $siteContext->get();
        if (!$site) {
            abort(404);
        }

        $category = Category::where('slug', $slug)->firstOrFail();
        $analyticsService->record($request);

        $posts = Post::published()
            ->whereHas('categories', function ($q) use ($category) {
                $q->where('be_categories.id', $category->id);
            })
            ->with(['author', 'categories'])
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        $seo = $seoService->getMetadata(null, $category);

        return view('frontend.category', compact('site', 'category', 'posts', 'seo'));
    }
}
