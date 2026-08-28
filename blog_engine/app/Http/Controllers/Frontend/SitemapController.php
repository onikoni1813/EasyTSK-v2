<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\SitePage;
use App\Services\SiteContext;

class SitemapController extends Controller
{
    public function index(SiteContext $siteContext)
    {
        $site = $siteContext->get();
        if (!$site) {
            abort(404);
        }

        $posts = Post::published()->select('slug', 'updated_at')->latest('updated_at')->get();
        $categories = Category::select('slug', 'updated_at')->get();
        $pages = SitePage::where('is_published', true)->select('slug', 'updated_at')->get();

        $content = view('frontend.sitemap', compact('site', 'posts', 'categories', 'pages'))->render();

        return response($content, 200)->header('Content-Type', 'application/xml');
    }
}
