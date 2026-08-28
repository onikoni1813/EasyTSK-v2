<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Tag;
use App\Services\AnalyticsService;
use App\Services\SeoService;
use App\Services\SiteContext;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function show(string $slug, Request $request, SiteContext $siteContext, SeoService $seoService, AnalyticsService $analyticsService)
    {
        $site = $siteContext->get();
        if (!$site) {
            abort(404);
        }

        $tag = Tag::where('slug', $slug)->firstOrFail();
        $analyticsService->record($request);

        $posts = Post::published()
            ->whereHas('tags', function ($q) use ($tag) {
                $q->where('be_tags.id', $tag->id);
            })
            ->with(['author', 'categories'])
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        $seo = [
            'title' => "Articles tagged with #{$tag->name} - " . $site->name,
            'description' => "Browse articles and guides tagged with {$tag->name} on {$site->name}.",
            'canonical' => url('/tag/' . $tag->slug),
            'og_image' => $site->logo ? url($site->logo) : null,
            'og_type' => 'website',
            'site_name' => $site->name,
            'favicon' => $site->favicon ? url($site->favicon) : null,
        ];

        return view('frontend.tag', compact('site', 'tag', 'posts', 'seo'));
    }
}
