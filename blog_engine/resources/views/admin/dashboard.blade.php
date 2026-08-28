@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Overview & Performance')

@section('content')
<div class="space-y-8">
    <!-- Active Site Banner -->
    @if($currentSite)
        <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Active Blog</span>
                    <span class="text-xs text-slate-400 font-mono">ID #{{ $currentSite->id }}</span>
                </div>
                <h3 class="text-xl font-bold text-white">{{ $currentSite->name }}</h3>
                <p class="text-xs text-slate-400 mt-0.5">{{ $currentSite->tagline ?: 'Multi-site Niche Blog' }} &bull; Subdomain: <span class="font-mono text-emerald-400">{{ $currentSite->subdomain }}.easytsk.com</span></p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.ads.index') }}" class="px-4 py-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 text-xs font-semibold rounded-xl border border-amber-500/30 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Manage Ads ({{ $stats['active_ads'] }} Active)</span>
                </a>
                <a href="{{ route('admin.posts.create') }}" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-slate-950 text-xs font-bold rounded-xl shadow-lg shadow-emerald-500/20 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>Create Post</span>
                </a>
            </div>
        </div>
    @endif

    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Metric 1 -->
        <div class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800/80 hover:border-slate-700 transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400">Total Network Sites</span>
                <span class="p-2 rounded-xl bg-blue-500/10 text-blue-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-white">{{ $stats['total_sites'] }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Managed Blogs (01 to 08+)</div>
        </div>

        <!-- Metric 2 -->
        <div class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800/80 hover:border-slate-700 transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400">Active Blog Articles</span>
                <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-white">{{ $stats['total_posts'] }}</div>
            <div class="text-[11px] text-emerald-400 mt-1">{{ $stats['published_posts'] }} published &bull; {{ $stats['draft_posts'] }} drafts</div>
        </div>

        <!-- Metric 3 -->
        <div class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800/80 hover:border-slate-700 transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400">Total Page Views</span>
                <span class="p-2 rounded-xl bg-purple-500/10 text-purple-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-white">{{ number_format($stats['total_views']) }}</div>
            <div class="text-[11px] text-purple-400 mt-1">+{{ $stats['today_views'] }} views today</div>
        </div>

        <!-- Metric 4 -->
        <div class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800/80 hover:border-slate-700 transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400">Ad Placements Active</span>
                <span class="p-2 rounded-xl bg-amber-500/10 text-amber-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-white">{{ $stats['active_ads'] }}</div>
            <div class="text-[11px] text-amber-400 mt-1">Adsterra & Monetag ready</div>
        </div>
    </div>

    <!-- 7-Day Traffic Visual Bar Chart -->
    <div class="p-6 rounded-2xl bg-slate-950/80 border border-slate-800/80">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h4 class="text-sm font-bold text-white">7-Day Traffic Trends</h4>
                <p class="text-xs text-slate-400">Page views for current active blog</p>
            </div>
            <span class="text-xs font-mono text-emerald-400">Realtime SQLite/MySQL Tracking</span>
        </div>

        <div class="grid grid-cols-7 gap-3 items-end h-40 pt-4 border-b border-slate-800 pb-2">
            @php
                $maxView = max(array_column($chartData, 'views') ?: [1]);
                $maxView = max($maxView, 10);
            @endphp
            @foreach($chartData as $point)
                @php
                    $pct = max(8, ($point['views'] / $maxView) * 100);
                @endphp
                <div class="flex flex-col items-center gap-2 h-full justify-end group">
                    <div class="text-[10px] text-slate-400 group-hover:text-emerald-300 font-mono">{{ $point['views'] }}</div>
                    <div class="w-full bg-slate-800 rounded-t-lg overflow-hidden group-hover:bg-slate-700 transition" style="height: {{ $pct }}%">
                        <div class="w-full h-full bg-gradient-to-t from-emerald-600 to-teal-400 opacity-80 group-hover:opacity-100 transition"></div>
                    </div>
                    <div class="text-[10px] text-slate-400 font-semibold truncate">{{ $point['date'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Two-column tables: Recent Posts & Top Posts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Articles -->
        <div class="p-6 rounded-2xl bg-slate-950/80 border border-slate-800/80 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-sm font-bold text-white">Recent Articles</h4>
                    <a href="{{ route('admin.posts.index') }}" class="text-xs text-emerald-400 hover:underline">View All &rarr;</a>
                </div>

                @if($recentPosts->isEmpty())
                    <p class="text-xs text-slate-500 py-6 text-center">No articles created for this site yet.</p>
                @else
                    <div class="divide-y divide-slate-800/60">
                        @foreach($recentPosts as $p)
                            <div class="py-3 flex items-center justify-between gap-4">
                                <div class="truncate">
                                    <a href="{{ route('admin.posts.edit', $p->id) }}" class="text-xs font-semibold text-slate-200 hover:text-emerald-400 truncate block">
                                        {{ $p->title }}
                                    </a>
                                    <div class="text-[10px] text-slate-500 mt-0.5">
                                        {{ $p->created_at->format('M d, Y') }} &bull; {{ $p->author?->name ?? 'Admin' }}
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $p->status === 'published' ? 'bg-emerald-500/10 text-emerald-400' : ($p->status === 'scheduled' ? 'bg-amber-500/10 text-amber-400' : 'bg-slate-800 text-slate-400') }}">
                                    {{ $p->status }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="pt-4 border-t border-slate-800 mt-4">
                <a href="{{ route('admin.posts.create') }}" class="block text-center py-2 bg-slate-900 hover:bg-slate-800 text-xs font-semibold text-slate-300 rounded-xl transition">
                    + Add New Article
                </a>
            </div>
        </div>

        <!-- Top Articles by Views -->
        <div class="p-6 rounded-2xl bg-slate-950/80 border border-slate-800/80 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-sm font-bold text-white">Top Performing Articles</h4>
                    <span class="text-[11px] text-slate-400">By Total Views</span>
                </div>

                @if($topPosts->isEmpty())
                    <p class="text-xs text-slate-500 py-6 text-center">No traffic recorded yet.</p>
                @else
                    <div class="divide-y divide-slate-800/60">
                        @foreach($topPosts as $p)
                            <div class="py-3 flex items-center justify-between gap-4">
                                <div class="truncate">
                                    <a href="{{ route('admin.posts.edit', $p->id) }}" class="text-xs font-semibold text-slate-200 hover:text-emerald-400 truncate block">
                                        {{ $p->title }}
                                    </a>
                                    <div class="text-[10px] text-slate-500 mt-0.5">
                                        Slug: /{{ $p->slug }}
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="text-xs font-mono font-bold text-emerald-400">{{ number_format($p->views_count) }}</span>
                                    <span class="text-[10px] text-slate-500 block">views</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="pt-4 border-t border-slate-800 mt-4">
                <a href="{{ route('admin.ads.index') }}" class="block text-center py-2 bg-amber-500/10 hover:bg-amber-500/20 text-xs font-semibold text-amber-300 rounded-xl transition">
                    Configure Monetization Placements &rarr;
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
