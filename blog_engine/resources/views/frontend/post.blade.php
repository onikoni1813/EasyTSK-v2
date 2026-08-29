@extends('layouts.frontend')

@inject('adEngine', 'App\Services\AdEngine')

@section('content')
<div class="space-y-6 sm:space-y-8">
    <!-- Breadcrumbs -->
    <nav class="flex items-center gap-1.5 sm:gap-2 text-[11px] sm:text-xs text-slate-500 overflow-x-auto whitespace-nowrap py-1">
        <a href="{{ route('home') }}" class="hover:text-slate-900 transition flex-shrink-0">Home</a>
        <span>/</span>
        @if($post->categories->isNotEmpty())
            <a href="{{ route('category.show', $post->categories->first()->slug) }}" class="hover:text-slate-900 transition font-medium flex-shrink-0">
                {{ $post->categories->first()->name }}
            </a>
            <span>/</span>
        @endif
        <span class="text-slate-800 font-semibold truncate max-w-[200px] sm:max-w-xs">{{ $post->title }}</span>
    </nav>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10">
        <!-- Article Main Column (8 cols) -->
        <article class="lg:col-span-8 bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-8 lg:p-10 border border-slate-200/80 shadow-sm space-y-6 sm:space-y-8 min-w-0">
            <!-- Article Header -->
            <header class="space-y-3 sm:space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    @foreach($post->categories as $cat)
                        <a href="{{ route('category.show', $cat->slug) }}" class="px-2.5 sm:px-3 py-0.5 sm:py-1 rounded-full text-[10px] sm:text-xs font-bold uppercase tracking-wider text-white" style="background-color: var(--brand-color)">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>

                <h1 class="text-xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900 leading-tight font-serif-title">
                    {{ $post->title }}
                </h1>

                @if($post->excerpt)
                    <p class="text-sm sm:text-base lg:text-lg text-slate-600 leading-relaxed font-medium">
                        {{ $post->excerpt }}
                    </p>
                @endif

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 sm:pt-4 border-t border-slate-100 text-xs text-slate-500">
                    <div class="flex items-center gap-2.5 sm:gap-3">
                        @if($post->author?->avatar)
                            <img src="{{ $post->author->avatar }}" alt="{{ $post->author->name }}" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full object-cover flex-shrink-0">
                        @else
                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-bold text-white text-xs sm:text-sm flex-shrink-0" style="background-color: var(--brand-color)">
                                {{ substr($post->author?->name ?? $site->name, 0, 1) }}
                            </div>
                        @endif
                        <div>
                            <span class="font-bold text-slate-900 block text-xs sm:text-sm">{{ $post->author?->name ?? $site->name }}</span>
                            <span class="text-[11px]">{{ $post->published_at ? $post->published_at->format('M j, Y') : $post->created_at->format('M j, Y') }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 text-[11px] sm:text-xs">
                        <span class="flex items-center gap-1 font-medium">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>{{ $post->estimated_reading_time }} min read</span>
                        </span>
                        <span>&bull;</span>
                        <span class="flex items-center gap-1 font-mono text-slate-400">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <span>{{ number_format($post->views_count) }} views</span>
                        </span>
                    </div>
                </div>
            </header>

            <!-- Before Content Ad Banner Slot -->
            @if($adEngine->has('before_content'))
                <div class="w-full my-4 sm:my-6 overflow-hidden">
                    {!! $adEngine->render('before_content') !!}
                </div>
            @endif

            <!-- Featured Image -->
            @if($post->image_url)
                <div class="rounded-xl sm:rounded-2xl overflow-hidden shadow-md">
                    <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="w-full h-auto object-cover max-h-[480px]">
                </div>
            @endif

            <!-- Rendered Body Content with Smart In-Content Ads -->
            <div class="article-content font-sans">
                {!! $contentWithAds !!}
            </div>

            <!-- After Content Ad Banner Slot -->
            @if($adEngine->has('after_content'))
                <div class="w-full my-6 sm:my-8 overflow-hidden">
                    {!! $adEngine->render('after_content') !!}
                </div>
            @endif

            <!-- Tags List -->
            @if($post->tags->isNotEmpty())
                <div class="pt-4 sm:pt-6 border-t border-slate-100 flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <span class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider mr-1">Tags:</span>
                    @foreach($post->tags as $t)
                        <a href="{{ route('tag.show', $t->slug) }}" class="px-2.5 py-0.5 sm:py-1 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs font-semibold text-slate-700 transition">
                            #{{ $t->name }}
                        </a>
                    @endforeach
                </div>
            @endif

            <!-- Social Share Bar -->
            <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <span class="text-xs font-bold text-slate-700">Share article:</span>
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <a href="https://twitter.com/intent/tweet?text={{ urlencode($post->title) }}&url={{ urlencode(url()->current()) }}" target="_blank" class="px-2.5 sm:px-3 py-1.5 bg-slate-900 hover:bg-black text-white text-[11px] sm:text-xs font-semibold rounded-lg transition">Twitter / X</a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" class="px-2.5 sm:px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[11px] sm:text-xs font-semibold rounded-lg transition">Facebook</a>
                    <a href="https://t.me/share/url?url={{ urlencode(url()->current()) }}&text={{ urlencode($post->title) }}" target="_blank" class="px-2.5 sm:px-3 py-1.5 bg-sky-500 hover:bg-sky-600 text-white text-[11px] sm:text-xs font-semibold rounded-lg transition">Telegram</a>
                    <button type="button" onclick="navigator.clipboard.writeText(window.location.href); alert('Article link copied to clipboard!');" class="px-2.5 sm:px-3 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-800 text-[11px] sm:text-xs font-semibold rounded-lg transition">Copy Link</button>
                </div>
            </div>

            <!-- Author Bio Box -->
            @if($post->author)
                <div class="p-4 sm:p-6 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-3 sm:gap-4">
                    @if($post->author->avatar)
                        <img src="{{ $post->author->avatar }}" alt="{{ $post->author->name }}" class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl object-cover flex-shrink-0">
                    @else
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl flex items-center justify-center font-black text-white text-lg sm:text-xl flex-shrink-0" style="background-color: var(--brand-color)">
                            {{ substr($post->author->name, 0, 1) }}
                        </div>
                    @endif
                    <div class="space-y-1">
                        <h4 class="text-sm font-bold text-slate-900">About {{ $post->author->name }}</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">{{ $post->author->bio ?: 'Senior Writer and Industry Analyst.' }}</p>
                    </div>
                </div>
            @endif
        </article>

        <!-- Right Sidebar (4 cols) -->
        <aside class="lg:col-span-4 space-y-6 sm:space-y-8">
            <!-- Sidebar Top Ad -->
            @if($adEngine->has('sidebar_top'))
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm text-center overflow-hidden">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block mb-2">Advertisement</span>
                    {!! $adEngine->render('sidebar_top') !!}
                </div>
            @endif

            <!-- Related Articles -->
            @if($relatedPosts->isNotEmpty())
                <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-sm space-y-4">
                    <h4 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-3">
                        Related Articles
                    </h4>

                    <div class="space-y-3 sm:space-y-4">
                        @foreach($relatedPosts as $rel)
                            <div class="space-y-1">
                                <h5 class="text-xs sm:text-sm font-bold text-slate-900 hover:text-slate-700 leading-snug">
                                    <a href="{{ route('post.show', $rel->slug) }}">
                                        {{ $rel->title }}
                                    </a>
                                </h5>
                                <div class="text-[10px] text-slate-400">
                                    {{ $rel->published_at ? $rel->published_at->format('M d, Y') : '' }} &bull; {{ $rel->estimated_reading_time }} min read
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Sticky Sidebar Ad Slot -->
            @if($adEngine->has('sidebar_sticky'))
                <div class="sticky top-24 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm text-center overflow-hidden">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block mb-2">Sponsored Content</span>
                    {!! $adEngine->render('sidebar_sticky') !!}
                </div>
            @endif
        </aside>
    </div>
</div>

@include('partials.task_reward_widget')
@endsection
