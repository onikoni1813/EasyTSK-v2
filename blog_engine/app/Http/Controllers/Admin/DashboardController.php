<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdPlacement;
use App\Models\Category;
use App\Models\DailyAnalytics;
use App\Models\PageView;
use App\Models\Post;
use App\Models\Site;
use App\Services\SiteContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(SiteContext $siteContext)
    {
        $currentSite = $siteContext->get();
        $allSites = Site::orderBy('name')->get();

        if (!$currentSite && $allSites->isNotEmpty()) {
            $currentSite = $allSites->first();
            $siteContext->set($currentSite);
            session(['admin_active_site_id' => $currentSite->id]);
        }

        $stats = [
            'total_sites' => $allSites->count(),
            'total_posts' => $currentSite ? Post::where('site_id', $currentSite->id)->count() : 0,
            'published_posts' => $currentSite ? Post::where('site_id', $currentSite->id)->where('status', 'published')->count() : 0,
            'draft_posts' => $currentSite ? Post::where('site_id', $currentSite->id)->where('status', 'draft')->count() : 0,
            'total_categories' => $currentSite ? Category::where('site_id', $currentSite->id)->count() : 0,
            'active_ads' => $currentSite ? AdPlacement::where('site_id', $currentSite->id)->where('is_active', true)->count() : 0,
            'total_views' => $currentSite ? PageView::where('site_id', $currentSite->id)->count() : 0,
            'today_views' => $currentSite ? PageView::where('site_id', $currentSite->id)->whereDate('visited_at', date('Y-m-d'))->count() : 0,
        ];

        // Recent posts for active site
        $recentPosts = $currentSite ? Post::where('site_id', $currentSite->id)
            ->with(['author', 'categories'])
            ->latest()
            ->take(5)
            ->get() : collect();

        // Top viewed posts
        $topPosts = $currentSite ? Post::where('site_id', $currentSite->id)
            ->orderByDesc('views_count')
            ->take(5)
            ->get() : collect();

        // Analytics chart (last 7 days)
        $chartData = [];
        if ($currentSite) {
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-$i days"));
                $views = PageView::where('site_id', $currentSite->id)->whereDate('visited_at', $d)->count();
                $chartData[] = [
                    'date' => date('M d', strtotime($d)),
                    'views' => $views,
                ];
            }
        }

        return view('admin.dashboard', compact('currentSite', 'allSites', 'stats', 'recentPosts', 'topPosts', 'chartData'));
    }

    public function switchSite($id, SiteContext $siteContext)
    {
        $site = Site::findOrFail($id);
        session(['admin_active_site_id' => $site->id]);
        $siteContext->set($site);

        return back()->with('success', "Switched active site to: {$site->name} ({$site->subdomain})");
    }
}
