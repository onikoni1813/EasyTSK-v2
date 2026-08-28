@extends('layouts.frontend')

@inject('adEngine', 'App\Services\AdEngine')

@section('content')
<div class="space-y-8 sm:space-y-10">

    <!-- Hero Featured Articles Grid -->
    @if($featuredPosts->isNotEmpty())
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6">
            @php
                $mainFeatured = $featuredPosts->first();
                $subFeatured = $featuredPosts->slice(1, 2);
            @endphp

            <!-- Main Big Featured Card (8 Cols) -->
            @if($mainFeatured)
                <div class="lg:col-span-8 group relative rounded-2xl sm:rounded-3xl overflow-hidden shadow-lg bg-slate-900 min-h-[300px] sm:min-h-[420px] lg:min-h-[460px] flex flex-col justify-end p-5 sm:p-8 lg:p-10">
                    @if($mainFeatured->featured_image)
                        <img src="{{ $mainFeatured->featured_image }}" alt="{{ $mainFeatured->title }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-700 opacity-60">
                    @else
                        <div class="absolute inset-0 bg-gradient-to-tr from-slate-950 via-slate-900 to-slate-800 opacity-90"></div>
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/60 to-transparent"></div>

                    <div class="relative z-10 space-y-2 sm:space-y-3">
                        @if($mainFeatured->categories->isNotEmpty())
                            <span class="inline-block px-2.5 sm:px-3 py-0.5 sm:py-1 rounded-full text-[10px] sm:text-xs font-bold uppercase tracking-wider text-white" style="background-color: var(--brand-color)">
                                {{ $mainFeatured->categories->first()->name }}
                            </span>
                        @endif

                        <h2 class="text-xl sm:text-3xl lg:text-4xl font-extrabold text-white leading-tight font-serif-title">
                            <a href="{{ route('post.show', $mainFeatured->slug) }}" class="hover:text-slate-200 transition">
                                {{ $mainFeatured->title }}
                            </a>
                        </h2>

                        <p class="text-xs sm:text-sm text-slate-300 line-clamp-2 max-w-2xl">
                            {{ $mainFeatured->excerpt ?: Str::limit(strip_tags($mainFeatured->content), 140) }}
                        </p>

                        <div class="flex flex-wrap items-center gap-2 sm:gap-4 text-[11px] sm:text-xs text-slate-400 pt-1 sm:pt-2">
                            <span>By {{ $mainFeatured->author?->name ?? $site->name }}</span>
                            <span>&bull;</span>
                            <span>{{ $mainFeatured->published_at ? $mainFeatured->published_at->format('M d, Y') : $mainFeatured->created_at->format('M d, Y') }}</span>
                            <span>&bull;</span>
                            <span>{{ $mainFeatured->estimated_reading_time }} min read</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Secondary Featured Cards (4 Cols) -->
            @if($subFeatured->isNotEmpty())
                <div class="lg:col-span-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-4 sm:gap-6">
                    @foreach($subFeatured as $sub)
                        <div class="group relative rounded-2xl sm:rounded-3xl overflow-hidden shadow-md bg-slate-900 min-h-[180px] sm:min-h-[200px] flex flex-col justify-end p-4 sm:p-6">
                            @if($sub->featured_image)
                                <img src="{{ $sub->featured_image }}" alt="{{ $sub->title }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-500 opacity-50">
                            @else
                                <div class="absolute inset-0 bg-gradient-to-tr from-slate-950 to-slate-800 opacity-90"></div>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/70 to-transparent"></div>

                            <div class="relative z-10 space-y-1.5 sm:space-y-2">
                                @if($sub->categories->isNotEmpty())
                                    <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-white px-2 py-0.5 rounded" style="background-color: var(--brand-color)">
                                        {{ $sub->categories->first()->name }}
                                    </span>
                                @endif
                                <h3 class="text-sm sm:text-base font-bold text-white leading-snug">
                                    <a href="{{ route('post.show', $sub->slug) }}" class="hover:text-slate-200 transition">
                                        {{ $sub->title }}
                                    </a>
                                </h3>
                                <div class="text-[10px] sm:text-[11px] text-slate-400">
                                    {{ $sub->published_at ? $sub->published_at->format('M d, Y') : $sub->created_at->format('M d, Y') }} &bull; {{ $sub->estimated_reading_time }} min read
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    <!-- Before Content Ad Banner Slot -->
    @if($adEngine->has('before_content'))
        <div class="w-full overflow-hidden">
            {!! $adEngine->render('before_content') !!}
        </div>
    @endif

    <!-- Main Content & Sidebar Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Left: Latest Articles Stream (8 cols) -->
        <div class="lg:col-span-8 space-y-6 sm:space-y-8">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3 sm:pb-4">
                <h3 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                    <span class="w-2 sm:w-2.5 h-5 sm:h-6 rounded-full" style="background-color: var(--brand-color)"></span>
                    <span>Latest Publications</span>
                </h3>
                <span class="text-[11px] sm:text-xs text-slate-500">{{ $posts->total() }} Articles</span>
            </div>

            <!-- Articles Feed List / Grid -->
            <div class="space-y-4 sm:space-y-6">
                @forelse($posts as $post)
                    <article class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 hover:border-slate-300 hover:shadow-md transition duration-200 flex flex-col sm:flex-row gap-4 sm:gap-6 group">
                        @if($post->featured_image)
                            <div class="w-full sm:w-52 md:w-56 h-48 sm:h-auto rounded-xl overflow-hidden bg-slate-100 flex-shrink-0 relative">
                                <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                @if($post->categories->isNotEmpty())
                                    <span class="absolute top-2.5 left-2.5 px-2 py-0.5 text-[9px] sm:text-[10px] font-bold uppercase rounded text-white shadow" style="background-color: var(--brand-color)">
                                        {{ $post->categories->first()->name }}
                                    </span>
                                @endif
                            </div>
                        @endif

                        <div class="flex-1 flex flex-col justify-between space-y-2 sm:space-y-3">
                            <div class="space-y-1.5 sm:space-y-2">
                                <div class="flex flex-wrap items-center gap-2 text-[11px] sm:text-xs text-slate-500">
                                    <span>{{ $post->author?->name ?? $site->name }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $post->published_at ? $post->published_at->format('M d, Y') : $post->created_at->format('M d, Y') }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $post->estimated_reading_time }} min read</span>
                                </div>

                                <h4 class="text-base sm:text-lg lg:text-xl font-bold text-slate-900 group-hover:text-slate-700 leading-snug font-serif-title">
                                    <a href="{{ route('post.show', $post->slug) }}">
                                        {{ $post->title }}
                                    </a>
                                </h4>

                                <p class="text-xs sm:text-sm text-slate-600 line-clamp-2 leading-relaxed">
                                    {{ $post->excerpt ?: Str::limit(strip_tags($post->content), 150) }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between pt-2 border-t border-slate-100 sm:border-0">
                                <a href="{{ route('post.show', $post->slug) }}" class="text-xs font-bold flex items-center gap-1 group-hover:underline" style="color: var(--brand-color)">
                                    <span>Read Full Article</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                                <span class="text-[10px] sm:text-[11px] text-slate-400 font-mono">{{ number_format($post->views_count) }} views</span>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="bg-white rounded-2xl p-8 sm:p-12 text-center border border-slate-200">
                        <p class="text-sm text-slate-500">No articles published yet. Stay tuned for fresh content!</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($posts->hasPages())
                <div class="pt-2">
                    {{ $posts->links() }}
                </div>
            @endif
        </div>

        <!-- Right: Monetized Sidebar (4 cols) -->
        <aside class="lg:col-span-4 space-y-6 sm:space-y-8">
            <!-- Sidebar Top Ad Placement -->
            @if($adEngine->has('sidebar_top'))
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm text-center overflow-hidden">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block mb-2">Advertisement</span>
                    {!! $adEngine->render('sidebar_top') !!}
                </div>
            @endif

            <!-- Trending / Popular Articles Widget -->
            @if($trendingPosts->isNotEmpty())
                <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-sm space-y-4">
                    <h4 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.527.82-1.11 1.96-1.585 3.013C7.94 7.27 7.41 8.543 7.02 9.5a14.28 14.28 0 00-.73 2.127 4.004 4.004 0 005.42 4.417 3.996 3.996 0 002.5-3.02 14.5 14.5 0 00.32-2.18c.08-.853.07-1.743-.02-2.56a9.92 9.92 0 00-.51-2.14 7.03 7.03 0 00-1.605-2.59zM10 16a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                        <span>Trending Today</span>
                    </h4>

                    <div class="space-y-3 sm:space-y-4 divide-y divide-slate-100">
                        @foreach($trendingPosts as $idx => $trend)
                            <div class="pt-3 first:pt-0 flex items-start gap-3 group">
                                <span class="font-black text-lg sm:text-xl text-slate-300 group-hover:text-slate-900 transition leading-none font-serif-title">
                                    0{{ $idx + 1 }}
                                </span>
                                <div class="space-y-1 min-w-0">
                                    <h5 class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-slate-700 leading-snug truncate sm:whitespace-normal">
                                        <a href="{{ route('post.show', $trend->slug) }}">
                                            {{ $trend->title }}
                                        </a>
                                    </h5>
                                    <div class="text-[10px] text-slate-400">
                                        {{ $trend->published_at ? $trend->published_at->format('M d') : '' }} &bull; {{ number_format($trend->views_count) }} reads
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Categories Widget -->
            @if($categories->isNotEmpty())
                <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-sm space-y-3 sm:space-y-4">
                    <h4 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-3">
                        Categories
                    </h4>

                    <div class="space-y-1 sm:space-y-2">
                        @foreach($categories as $cat)
                            <a href="{{ route('category.show', $cat->slug) }}" class="flex items-center justify-between text-xs font-semibold text-slate-600 hover:text-slate-900 py-1.5 px-2 rounded-lg hover:bg-slate-50 transition">
                                <span>{{ $cat->name }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-500 font-mono">{{ $cat->posts_count }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Sticky Sidebar Ad Placement -->
            @if($adEngine->has('sidebar_sticky'))
                <div class="sticky top-24 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm text-center overflow-hidden">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block mb-2">Sponsored</span>
                    {!! $adEngine->render('sidebar_sticky') !!}
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection
