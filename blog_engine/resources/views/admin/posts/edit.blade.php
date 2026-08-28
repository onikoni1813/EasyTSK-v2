@extends('layouts.admin')

@section('title', 'Edit Article')
@section('page-title', 'Edit Article: ' . $post->title)

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold text-white">Edit Article</h3>
            <p class="text-xs text-slate-400">Target Site: <span class="text-emerald-400 font-semibold">{{ $site->name }}</span> ({{ $site->subdomain }}.easytsk.com)</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('post.show', ['slug' => $post->slug, 'site' => $site->id]) }}" target="_blank" class="text-xs text-emerald-400 hover:underline flex items-center gap-1">
                <span>View Live</span>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
            </a>
            <a href="{{ route('admin.posts.index') }}" class="text-xs text-slate-400 hover:text-white">&larr; Back to Articles</a>
        </div>
    </div>

    <form action="{{ route('admin.posts.update', $post->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content Column (2 cols) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Title & Slug -->
                <div class="p-6 rounded-2xl bg-slate-950/80 border border-slate-800/80 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Article Title *</label>
                        <input type="text" name="title" value="{{ old('title', $post->title) }}" required class="w-full px-4 py-3 bg-slate-900 border border-slate-800 rounded-xl text-base font-semibold text-white placeholder-slate-600 focus:border-emerald-500 focus:outline-none transition">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">URL Slug *</label>
                        <div class="flex items-center">
                            <span class="px-3 py-2.5 bg-slate-800 text-slate-400 text-xs border border-r-0 border-slate-800 rounded-l-xl font-mono">/</span>
                            <input type="text" name="slug" value="{{ old('slug', $post->slug) }}" required class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-r-xl text-xs text-white font-mono focus:border-emerald-500 focus:outline-none transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Short Excerpt / Summary</label>
                        <textarea name="excerpt" rows="2" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-slate-200 placeholder-slate-600 focus:border-emerald-500 focus:outline-none transition">{{ old('excerpt', $post->excerpt) }}</textarea>
                    </div>
                </div>

                <!-- Rich Content Area -->
                <div class="p-6 rounded-2xl bg-slate-950/80 border border-slate-800/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-slate-300">Article Body Content (HTML supported) *</label>
                        <span class="text-[11px] text-emerald-400 font-mono">Auto Ad-Injection Active</span>
                    </div>

                    <!-- Quick HTML Toolbar Helper -->
                    <div class="flex flex-wrap gap-1.5 p-2 bg-slate-900 border border-slate-800 rounded-xl text-xs">
                        <button type="button" onclick="insertTag('<h2>', '</h2>')" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 rounded text-slate-300 font-bold">H2</button>
                        <button type="button" onclick="insertTag('<h3>', '</h3>')" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 rounded text-slate-300 font-bold">H3</button>
                        <button type="button" onclick="insertTag('<strong>', '</strong>')" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 rounded text-slate-300 font-bold">B</button>
                        <button type="button" onclick="insertTag('<em>', '</em>')" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 rounded text-slate-300 italic">I</button>
                        <button type="button" onclick="insertTag('<p>', '</p>')" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 rounded text-slate-300">&lt;P&gt;</button>
                        <button type="button" onclick="insertTag('<blockquote>', '</blockquote>')" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 rounded text-slate-300">Quote</button>
                        <button type="button" onclick="insertTag('<ul>\n  <li>', '</li>\n</ul>')" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 rounded text-slate-300">Bullet List</button>
                    </div>

                    <textarea id="postContent" name="content" rows="16" required class="w-full px-4 py-3 bg-slate-900 border border-slate-800 rounded-xl text-sm font-sans text-slate-100 placeholder-slate-600 focus:border-emerald-500 focus:outline-none transition leading-relaxed">{{ old('content', $post->content) }}</textarea>
                </div>

                <!-- SEO Meta Settings -->
                <div class="p-6 rounded-2xl bg-slate-950/80 border border-slate-800/80 space-y-4">
                    <h4 class="text-sm font-bold text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <span>SEO & Schema Meta Defaults</span>
                    </h4>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Custom Meta Title</label>
                            <input type="text" name="meta_title" value="{{ old('meta_title', $post->meta_title) }}" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Meta Description (150-160 chars)</label>
                            <textarea name="meta_description" rows="2" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">{{ old('meta_description', $post->meta_description) }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Canonical URL</label>
                                <input type="url" name="canonical_url" value="{{ old('canonical_url', $post->canonical_url) }}" placeholder="https://..." class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Schema Type</label>
                                <select name="schema_type" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
                                    <option value="Article" {{ $post->schema_type === 'Article' ? 'selected' : '' }}>Article</option>
                                    <option value="BlogPosting" {{ $post->schema_type === 'BlogPosting' ? 'selected' : '' }}>BlogPosting</option>
                                    <option value="NewsArticle" {{ $post->schema_type === 'NewsArticle' ? 'selected' : '' }}>NewsArticle</option>
                                    <option value="TechArticle" {{ $post->schema_type === 'TechArticle' ? 'selected' : '' }}>TechArticle</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Column (1 col) -->
            <div class="space-y-6">
                <!-- Publishing Controls -->
                <div class="p-6 rounded-2xl bg-slate-950/80 border border-slate-800/80 space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-bold text-white">Publish Status</h4>
                        <span class="text-xs font-mono text-emerald-400">{{ number_format($post->views_count) }} views</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Status</label>
                        <select name="status" class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs font-semibold text-white focus:border-emerald-500 focus:outline-none">
                            <option value="published" {{ $post->status === 'published' ? 'selected' : '' }}>Published (Live)</option>
                            <option value="draft" {{ $post->status === 'draft' ? 'selected' : '' }}>Draft (Private)</option>
                            <option value="scheduled" {{ $post->status === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Publish Date / Schedule</label>
                        <input type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at ? $post->published_at->format('Y-m-d\TH:i') : '') }}" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
                    </div>

                    <div class="space-y-2 pt-2 border-t border-slate-800">
                        <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                            <input type="checkbox" name="is_featured" value="1" {{ $post->is_featured ? 'checked' : '' }} class="w-4 h-4 rounded bg-slate-900 border-slate-800 text-emerald-500 focus:ring-emerald-500">
                            <span>Featured on Homepage Hero</span>
                        </label>
                        <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                            <input type="checkbox" name="is_trending" value="1" {{ $post->is_trending ? 'checked' : '' }} class="w-4 h-4 rounded bg-slate-900 border-slate-800 text-emerald-500 focus:ring-emerald-500">
                            <span>Trending / Top Bar Badge</span>
                        </label>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="w-full py-3 bg-emerald-500 hover:bg-emerald-600 font-bold text-slate-950 text-xs rounded-xl shadow-lg shadow-emerald-500/20 transition">
                            Update Article
                        </button>
                    </div>
                </div>

                <!-- Featured Image -->
                <div class="p-6 rounded-2xl bg-slate-950/80 border border-slate-800/80 space-y-3">
                    <h4 class="text-sm font-bold text-white">Featured Image</h4>
                    @if($post->featured_image)
                        <div class="relative rounded-xl overflow-hidden border border-slate-800">
                            <img src="{{ $post->featured_image }}" alt="" class="w-full h-32 object-cover">
                        </div>
                    @endif
                    <input type="file" name="featured_image" accept="image/*" class="block w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700">
                </div>

                <!-- Categories -->
                <div class="p-6 rounded-2xl bg-slate-950/80 border border-slate-800/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-bold text-white">Categories</h4>
                        <a href="{{ route('admin.categories.index') }}" class="text-[11px] text-emerald-400 hover:underline">+ New Cat</a>
                    </div>

                    <div class="max-h-40 overflow-y-auto space-y-2 pr-2">
                        @foreach($categories as $cat)
                            <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer hover:text-white">
                                <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}" {{ in_array($cat->id, $selectedCategories) ? 'checked' : '' }} class="w-3.5 h-3.5 rounded bg-slate-900 border-slate-800 text-emerald-500 focus:ring-emerald-500">
                                <span>{{ $cat->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Tags -->
                <div class="p-6 rounded-2xl bg-slate-950/80 border border-slate-800/80 space-y-3">
                    <h4 class="text-sm font-bold text-white">Tags (Comma Separated)</h4>
                    <input type="text" name="tags_string" value="{{ old('tags_string', $tagsString) }}" placeholder="crypto, defi, 2026" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
                </div>

                <!-- Author -->
                <div class="p-6 rounded-2xl bg-slate-950/80 border border-slate-800/80 space-y-3">
                    <h4 class="text-sm font-bold text-white">Author Profile</h4>
                    <select name="author_id" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
                        <option value="">Default Site Editorial Team</option>
                        @foreach($authors as $author)
                            <option value="{{ $author->id }}" {{ old('author_id', $post->author_id) == $author->id ? 'selected' : '' }}>{{ $author->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function insertTag(openTag, closeTag) {
        const textarea = document.getElementById('postContent');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const selected = text.substring(start, end);
        textarea.value = text.substring(0, start) + openTag + selected + closeTag + text.substring(end);
        textarea.focus();
        textarea.setSelectionRange(start + openTag.length, end + openTag.length);
    }
</script>
@endpush
@endsection
