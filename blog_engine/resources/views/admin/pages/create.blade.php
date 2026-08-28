@extends('layouts.admin')

@section('title', 'Create Page')
@section('page-title', 'Add Legal / Static Page')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold text-white">Create Page</h3>
            <p class="text-xs text-slate-400">Site: <span class="text-emerald-400 font-semibold">{{ $site->name }}</span></p>
        </div>
        <a href="{{ route('admin.pages.index') }}" class="text-xs text-slate-400 hover:text-white">&larr; Back to Pages</a>
    </div>

    <form action="{{ route('admin.pages.store') }}" method="POST" class="bg-slate-950/80 border border-slate-800/80 rounded-2xl p-6 sm:p-8 space-y-6">
        @csrf

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Page Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" placeholder="e.g. Privacy Policy" required class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-sm text-white focus:border-emerald-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">URL Slug (Optional)</label>
                <div class="flex items-center">
                    <span class="px-3 py-2.5 bg-slate-800 text-slate-400 text-xs border border-r-0 border-slate-800 rounded-l-xl font-mono">/page/</span>
                    <input type="text" name="slug" value="{{ old('slug') }}" placeholder="privacy-policy" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-r-xl text-xs text-white font-mono focus:border-emerald-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Page Content (HTML allowed) *</label>
                <textarea name="content" rows="12" required class="w-full px-4 py-3 bg-slate-900 border border-slate-800 rounded-xl text-sm font-sans text-white focus:border-emerald-500 focus:outline-none" placeholder="<p>Full page terms or policy content...</p>">{{ old('content') }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">SEO Meta Title</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title') }}" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">SEO Meta Description</label>
                    <input type="text" name="meta_description" value="{{ old('meta_description') }}" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
            <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" name="is_published" value="1" checked class="w-4 h-4 rounded bg-slate-900 border-slate-800 text-emerald-500 focus:ring-emerald-500">
                <span>Publish Page Live</span>
            </label>

            <button type="submit" class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 font-bold text-slate-950 text-xs rounded-xl shadow-lg shadow-emerald-500/20 transition">
                Create Page
            </button>
        </div>
    </form>
</div>
@endsection
