<?php

namespace App\Services;

use App\Models\DailyAnalytics;
use App\Models\PageView;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function __construct(protected SiteContext $siteContext)
    {
    }

    /**
     * Record a pageview in background/lifecycle (shared hosting compliant).
     */
    public function record(Request $request, ?Post $post = null): void
    {
        $site = $this->siteContext->get();
        if (!$site) {
            return;
        }

        // Don't track admin panel routes or bot probes
        if ($request->is('admin*') || $request->is('api*') || $request->is('sitemap.xml') || $request->is('robots.txt')) {
            return;
        }

        $userAgent = $request->userAgent() ?? '';
        $deviceType = 'desktop';
        if (preg_match('/(mobile|android|iphone|ipad|phone)/i', $userAgent)) {
            $deviceType = preg_match('/(tablet|ipad)/i', $userAgent) ? 'tablet' : 'mobile';
        }

        $ip = $request->ip() ?? '127.0.0.1';
        $ipHash = hash('sha256', $ip . date('Y-m-d')); // Daily salt for privacy

        try {
            PageView::create([
                'site_id' => $site->id,
                'post_id' => $post?->id,
                'path' => '/' . ltrim($request->path(), '/'),
                'ip_hash' => $ipHash,
                'user_agent' => substr($userAgent, 0, 250),
                'device_type' => $deviceType,
                'referer' => substr($request->headers->get('referer', ''), 0, 250),
                'visited_at' => now(),
            ]);

            // Increment post views counter if on a single post
            if ($post) {
                $post->incrementQuietly('views_count');
            }
        } catch (\Throwable $e) {
            // Fail silently so user experience is never degraded
        }
    }

    /**
     * Daily aggregation rollup (called by cron schedule:run).
     */
    public function aggregateDaily(): void
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $datesToAggregate = [$yesterday, $today];

        foreach ($datesToAggregate as $date) {
            $stats = DB::table('be_page_views')
                ->select(
                    'site_id',
                    DB::raw('COUNT(*) as total_views'),
                    DB::raw('COUNT(DISTINCT ip_hash) as unique_visits')
                )
                ->whereDate('visited_at', $date)
                ->groupBy('site_id')
                ->get();

            foreach ($stats as $row) {
                DailyAnalytics::updateOrCreate(
                    [
                        'site_id' => $row->site_id,
                        'date' => $date,
                    ],
                    [
                        'page_views' => $row->total_views,
                        'unique_visitors' => $row->unique_visits,
                    ]
                );
            }
        }

        // Prune raw page_views older than 30 days to keep DB ultra-light on shared hosting
        $pruneDate = date('Y-m-d H:i:s', strtotime('-30 days'));
        DB::table('be_page_views')->where('visited_at', '<', $pruneDate)->delete();
    }
}
