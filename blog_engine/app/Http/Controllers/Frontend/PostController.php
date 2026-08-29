<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\AdEngine;
use App\Services\AnalyticsService;
use App\Services\SeoService;
use App\Services\SiteContext;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function show(string $slug, Request $request, SiteContext $siteContext, SeoService $seoService, AdEngine $adEngine, AnalyticsService $analyticsService)
    {
        $site = $siteContext->get();
        if (!$site) {
            abort(404);
        }

        $post = Post::published()
            ->where('slug', $slug)
            ->with(['author', 'categories', 'tags'])
            ->firstOrFail();

        // Record pageview asynchronously / quietly
        $analyticsService->record($request, $post);

        // Smart in-content ad injection
        $contentWithAds = $adEngine->injectInContent($post->content);

        // Normalize relative local image paths in HTML body with exact base path
        $base = $request->getBasePath();
        $contentWithAds = preg_replace_callback('/src=["\'](\/(images|storage)\/[^"\']+)["\']/i', function ($m) use ($base) {
            $rel = ltrim($m[1], '/');
            $url = $base ? (rtrim($base, '/') . '/' . $rel) : ('/' . $rel);
            return 'src="' . $url . '"';
        }, $contentWithAds);

        // Related posts in same categories
        $categoryIds = $post->categories->pluck('id')->toArray();
        $relatedPosts = Post::published()
            ->where('id', '!=', $post->id)
            ->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('be_categories.id', $categoryIds);
            })
            ->latest('published_at')
            ->take(3)
            ->get();

        $seo = $seoService->getMetadata($post);
        $schema = $seoService->generateSchema($post);

        return view('frontend.post', compact('site', 'post', 'contentWithAds', 'relatedPosts', 'seo', 'schema'));
    }
}
