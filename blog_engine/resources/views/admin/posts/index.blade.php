@extends('layouts.admin')

@section('title', 'Manage Posts')
@section('page-title', 'Articles for: ' . ($site ? $site->name : 'All'))

@section('content')
<div class="space-y-6">
    <!-- Top Filter & Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Search & Filter Form -->
        <form action="{{ route('admin.posts.index') }}" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search articles by title..." class="px-4 py-2 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none w-64">

            <select name="status" class="px-3 py-2 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-slate-300 focus:border-emerald-500 focus:outline-none">
                <option value="">All Statuses</option>
                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
            </select>

            <select name="category_id" class="px-3 py-2 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-slate-300 focus:border-emerald-500 focus:outline-none">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 rounded-xl transition">
                Filter
            </button>
            @if(request()->hasAny(['search', 'status', 'category_id']))
                <a href="{{ route('admin.posts.index') }}" class="text-xs text-slate-400 hover:text-white underline">Clear</a>
            @endif
        </form>

        <a href="{{ route('admin.posts.create') }}" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold text-xs rounded-xl shadow-lg shadow-emerald-500/20 flex items-center gap-1.5 transition self-start md:self-auto flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>Write New Article</span>
        </a>
    </div>

    <!-- Posts Table -->
    <div class="bg-slate-950/80 border border-slate-800/80 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto w-full">
            <table class="w-full min-w-[650px] text-left text-xs text-slate-300">
                <thead class="bg-slate-900/90 text-slate-400 uppercase text-[10px] font-bold tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Article</th>
                        <th class="px-6 py-4">Categories</th>
                        <th class="px-6 py-4">Author</th>
                        <th class="px-6 py-4 text-center">Views</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($posts as $post)
                        <tr class="hover:bg-slate-900/40 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($post->featured_image)
                                        <img src="{{ $post->featured_image }}" alt="" class="w-10 h-10 rounded-lg object-cover bg-slate-900 border border-slate-800 flex-shrink-0">
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-600 flex-shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        </div>
                                    @endif
                                    <div class="max-w-md truncate">
                                        <a href="{{ route('admin.posts.edit', $post->id) }}" class="font-bold text-slate-100 hover:text-emerald-400 transition block truncate">
                                            {{ $post->title }}
                                        </a>
                                        <div class="text-[11px] text-slate-500 font-mono mt-0.5 truncate">/{{ $post->slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($post->categories as $cat)
                                        <span class="px-2 py-0.5 bg-slate-900 border border-slate-800 rounded text-[10px] text-slate-300">{{ $cat->name }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-400">
                                {{ $post->author?->name ?? 'Default Admin' }}
                            </td>
                            <td class="px-6 py-4 text-center font-mono font-bold text-emerald-400">
                                {{ number_format($post->views_count) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $post->status === 'published' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : ($post->status === 'scheduled' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700') }}">
                                    {{ $post->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-[11px] text-slate-400 whitespace-nowrap">
                                {{ $post->published_at ? $post->published_at->format('M d, Y') : $post->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('post.show', ['slug' => $post->slug, 'site' => $site->id]) }}" target="_blank" class="p-1.5 bg-slate-800 hover:bg-slate-700 text-emerald-400 rounded-lg transition" title="Preview Article">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </a>
                                    <a href="{{ route('admin.posts.edit', $post->id) }}" class="p-1.5 bg-slate-800 hover:bg-slate-700 text-blue-400 rounded-lg transition" title="Edit Article">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </a>
                                    <form action="{{ route('admin.posts.destroy', $post->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this article?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 bg-slate-800 hover:bg-rose-900/50 text-rose-400 rounded-lg transition" title="Delete Article">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                No articles found for this blog. Click "+ Write New Article" to add one!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($posts->hasPages())
            <div class="px-6 py-4 border-t border-slate-800">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
