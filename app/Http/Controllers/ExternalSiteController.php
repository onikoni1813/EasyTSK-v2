<?php

namespace App\Http\Controllers;

use App\Models\SiteCategory;
use App\Models\SitePage;
use App\Models\SitePost;
use App\Models\Tool;
use App\Services\SiteContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExternalSiteController extends Controller
{
    public function index(Request $request)
    {
        $site = SiteContext::current();

        if (!$site) {
            return Inertia::render('Errors/UnknownDomain', [
                'host' => $request->getHost(),
            ])->toResponse($request)->setStatusCode(404);
        }

        if ($site->status === 'inactive') {
            return Inertia::render('Errors/SiteDisabled', [
                'siteName' => $site->name,
            ])->toResponse($request)->setStatusCode(404);
        }

        if ($site->status === 'maintenance') {
            return Inertia::render('External/Themes/Default/Maintenance', [
                'siteName' => $site->name,
                'message' => $site->getSetting('maintenance_message', 'This site is currently undergoing maintenance. Please check back soon.'),
            ])->toResponse($request)->setStatusCode(503);
        }

        if ($site->siteType && $site->siteType->slug === 'promos') {
            return $this->promosIndex($request);
        }

        $featuredTools = $site->tools()
            ->where('is_active', true)
            ->with('category')
            ->get();

        $latestPosts = SitePost::where('site_id', $site->id)
            ->published()
            ->with('category')
            ->latest()
            ->take(6)
            ->get();

        return Inertia::render('External/Themes/Default/Index', [
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'slug' => $site->slug,
                'meta_title' => $site->meta_title ?? $site->name,
                'meta_description' => $site->meta_description ?? ('Welcome to ' . $site->name),
            ],
            'tools' => $featuredTools,
            'posts' => $latestPosts,
        ]);
    }

    public function promosIndex(Request $request)
    {
        $site = SiteContext::current();

        if (!$site || $site->status !== 'active') {
            return Inertia::render('External/Themes/Default/NotFound', [
                'slug' => 'promos',
            ])->toResponse($request)->setStatusCode(404);
        }

        $search = $request->query('search');
        $categorySlug = $request->query('category');

        $query = SitePost::where('site_id', $site->id)
            ->published()
            ->with('category');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($categorySlug) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        $deals = $query->latest()->paginate(12)->withQueryString();
        $categories = SiteCategory::where('site_id', $site->id)->get();

        return Inertia::render('External/Themes/Default/PromosIndex', [
            'site' => [
                'name' => $site->name,
                'meta_title' => $site->meta_title ?? $site->name,
                'meta_description' => $site->meta_description,
            ],
            'deals' => $deals,
            'categories' => $categories,
            'filters' => [
                'search' => $search ?: '',
                'category' => $categorySlug ?: '',
            ],
        ]);
    }

    public function toolsIndex(Request $request)
    {
        $site = SiteContext::current();

        if (!$site || $site->status !== 'active') {
            return Inertia::render('External/Themes/Default/NotFound', [
                'slug' => 'tools',
            ])->toResponse($request)->setStatusCode(404);
        }

        $tools = $site->tools()
            ->where('is_active', true)
            ->with('category')
            ->get();

        return Inertia::render('External/Themes/Default/ToolsIndex', [
            'tools' => $tools,
        ]);
    }

    public function toolShow(Request $request, string $slug)
    {
        $site = SiteContext::current();

        if (!$site || $site->status !== 'active') {
            return Inertia::render('External/Themes/Default/NotFound', [
                'slug' => $slug,
            ])->toResponse($request)->setStatusCode(404);
        }

        $tool = Tool::where('slug', strtolower($slug))
            ->where('is_active', true)
            ->with('category')
            ->first();

        if (!$tool || !$site->tools()->where('tool_id', $tool->id)->exists()) {
            return Inertia::render('External/Themes/Default/NotFound', [
                'slug' => $slug,
            ])->toResponse($request)->setStatusCode(404);
        }

        return Inertia::render('External/Themes/Default/ToolShow', [
            'tool' => [
                'id' => $tool->id,
                'name' => $tool->name,
                'slug' => $tool->slug,
                'summary' => $tool->summary,
                'description' => $tool->description,
                'component_name' => $tool->component_name,
                'execution_type' => $tool->execution_type,
                'category' => $tool->category ? $tool->category->name : null,
                'meta_title' => $tool->meta_title ?? ($tool->name . ' — Free Online Tool'),
                'meta_description' => $tool->meta_description ?? $tool->summary,
                'meta_keywords' => $tool->meta_keywords,
            ],
        ]);
    }

    public function articles(Request $request)
    {
        $site = SiteContext::current();

        if (!$site || $site->status !== 'active') {
            return Inertia::render('External/Themes/Default/NotFound', [
                'slug' => 'articles',
            ])->toResponse($request)->setStatusCode(404);
        }

        $search = $request->query('search');
        $categorySlug = $request->query('category');

        $query = SitePost::where('site_id', $site->id)
            ->published()
            ->with('category');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($categorySlug) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        $posts = $query->latest()->paginate(12)->withQueryString();
        $categories = SiteCategory::where('site_id', $site->id)->get();

        return Inertia::render('External/Themes/Default/ArticlesIndex', [
            'posts' => $posts,
            'categories' => $categories,
            'filters' => [
                'search' => $search ?: '',
                'category' => $categorySlug ?: '',
            ],
        ]);
    }

    public function page(Request $request, string $slug)
    {
        $site = SiteContext::current();

        if (!$site || $site->status !== 'active') {
            return Inertia::render('External/Themes/Default/NotFound', [
                'slug' => $slug,
            ])->toResponse($request)->setStatusCode(404);
        }

        // 1. Try DB Page
        $dbPage = SitePage::where('site_id', $site->id)
            ->where('slug', strtolower($slug))
            ->where('is_published', true)
            ->first();

        if ($dbPage) {
            return Inertia::render('External/Themes/Default/Page', [
                'page' => [
                    'id' => $dbPage->id,
                    'title' => $dbPage->title,
                    'slug' => $dbPage->slug,
                    'content' => $dbPage->content,
                    'meta_title' => $dbPage->meta_title ?? $dbPage->title,
                    'meta_description' => $dbPage->meta_description,
                    'meta_keywords' => $dbPage->meta_keywords,
                ],
            ]);
        }

        // 2. Try DB Post / Article
        $dbPost = SitePost::where('site_id', $site->id)
            ->where('slug', strtolower($slug))
            ->where('is_published', true)
            ->with('category')
            ->first();

        if ($dbPost) {
            $relatedPostsQuery = SitePost::where('site_id', $site->id)
                ->where('id', '!=', $dbPost->id)
                ->where('is_published', true)
                ->with('category');

            if ($dbPost->category_id) {
                $relatedPostsQuery->where('category_id', $dbPost->category_id);
            }

            $relatedPosts = $relatedPostsQuery->latest()->take(3)->get();

            return Inertia::render('External/Themes/Default/Article', [
                'post' => [
                    'id' => $dbPost->id,
                    'title' => $dbPost->title,
                    'slug' => $dbPost->slug,
                    'summary' => $dbPost->summary,
                    'content' => $dbPost->content,
                    'featured_image' => $dbPost->featured_image,
                    'reading_time_minutes' => $dbPost->reading_time_minutes,
                    'author_name' => 'EasyTSK Tech Team',
                    'category' => $dbPost->category ? [
                        'name' => $dbPost->category->name,
                        'slug' => $dbPost->category->slug,
                    ] : null,
                    'published_at' => $dbPost->published_at ? $dbPost->published_at->toFormattedDateString() : null,
                    'meta_title' => $dbPost->meta_title ?? $dbPost->title,
                    'meta_description' => $dbPost->meta_description ?? $dbPost->summary,
                    'meta_keywords' => $dbPost->meta_keywords,
                ],
                'relatedPosts' => $relatedPosts,
            ]);
        }

        // 3. Fallback Legal Pages
        $legalPages = ['about', 'privacy', 'terms', 'contact', 'cookie-policy'];
        if (in_array(strtolower($slug), $legalPages)) {
            return Inertia::render('External/Themes/Default/Page', [
                'page' => [
                    'title' => ucfirst(str_replace('-', ' ', $slug)),
                    'slug' => $slug,
                    'content' => $site->getSetting('page_' . str_replace('-', '_', $slug) . '_content', 'Information for ' . $slug . ' page.'),
                    'meta_title' => ucfirst(str_replace('-', ' ', $slug)) . ' - ' . $site->name,
                    'meta_description' => 'Official ' . str_replace('-', ' ', $slug) . ' page for ' . $site->name,
                ],
            ]);
        }

        // 4. 404 Not Found
        return Inertia::render('External/Themes/Default/NotFound', [
            'slug' => $slug,
        ])->toResponse($request)->setStatusCode(404);
    }
}
