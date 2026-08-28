@extends('layouts.frontend')

@inject('adEngine', 'App\Services\AdEngine')

@section('content')
<div class="space-y-6 sm:space-y-8">
    <!-- Category Header Banner -->
    <div class="bg-white rounded-2xl sm:rounded-3xl p-5 sm:p-8 border border-slate-200/80 shadow-sm space-y-2 sm:space-y-3">
        <div class="flex items-center gap-2">
            <span class="px-2.5 sm:px-3 py-0.5 sm:py-1 rounded-full text-[10px] sm:text-xs font-bold uppercase tracking-wider text-white" style="background-color: var(--brand-color)">Category Archive</span>
            <span class="text-xs text-slate-400 font-mono">{{ $posts->total() }} Articles</span>
        </div>
        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900 font-serif-title">{{ $category->name }}</h1>
        @if($category->description)
            <p class="text-xs sm:text-sm text-slate-600 max-w-2xl">{{ $category->description }}</p>
        @endif
    </div>

    <!-- Ad Slot -->
    @if($adEngine->has('before_content'))
        <div class="w-full overflow-hidden">
            {!! $adEngine->render('before_content') !!}
        </div>
    @endif

    <!-- Articles Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        @forelse($posts as $post)
            <article class="bg-white rounded-2xl overflow-hidden border border-slate-200/80 hover:border-slate-300 hover:shadow-md transition group flex flex-col justify-between">
                <div>
                    @if($post->featured_image)
                        <div class="h-44 sm:h-48 overflow-hidden bg-slate-100 relative">
                            <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        </div>
                    @else
                        <div class="h-28 sm:h-32 bg-slate-100 flex items-center justify-center text-slate-400">
                            <span class="text-xs font-bold uppercase">{{ $category->name }}</span>
                        </div>
                    @endif

                    <div class="p-4 sm:p-6 space-y-2">
                        <div class="flex items-center gap-2 text-[11px] text-slate-400">
                            <span>{{ $post->published_at ? $post->published_at->format('M d, Y') : $post->created_at->format('M d, Y') }}</span>
                            <span>&bull;</span>
                            <span>{{ $post->estimated_reading_time }} min read</span>
                        </div>

                        <h2 class="text-base sm:text-lg font-bold text-slate-900 group-hover:text-slate-700 leading-snug font-serif-title">
                            <a href="{{ route('post.show', $post->slug) }}">
                                {{ $post->title }}
                            </a>
                        </h2>

                        <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
                            {{ $post->excerpt ?: Str::limit(strip_tags($post->content), 120) }}
                        </p>
                    </div>
                </div>

                <div class="px-4 sm:px-6 pb-4 sm:pb-6 pt-2 flex items-center justify-between border-t border-slate-50 text-xs">
                    <span class="font-bold" style="color: var(--brand-color)">Read More &rarr;</span>
                    <span class="text-slate-400 font-mono text-[10px] sm:text-[11px]">{{ number_format($post->views_count) }} views</span>
                </div>
            </article>
        @empty
            <div class="col-span-full bg-white rounded-2xl p-8 sm:p-12 text-center border border-slate-200">
                <p class="text-sm text-slate-500">No articles filed under this category yet.</p>
            </div>
        @endforelse
    </div>

    @if($posts->hasPages())
        <div class="pt-4">
            {{ $posts->links() }}
        </div>
    @endif
</div>
@endsection
