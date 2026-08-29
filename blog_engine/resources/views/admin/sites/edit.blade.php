@extends('layouts.admin')

@section('title', 'Edit Blog Site')
@section('page-title', 'Edit Site: ' . $site->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold text-white">Edit Site Details</h3>
            <p class="text-xs text-slate-400">Modifications only apply to this isolated tenant blog.</p>
        </div>
        <a href="{{ route('admin.sites.index') }}" class="text-xs text-slate-400 hover:text-white">&larr; Back to Sites</a>
    </div>

    <form action="{{ route('admin.sites.update', $site->id) }}" method="POST" class="bg-slate-950/80 border border-slate-800/80 rounded-2xl p-6 sm:p-8 space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Site Name *</label>
                <input type="text" name="name" value="{{ old('name', $site->name) }}" required class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-sm text-white focus:border-emerald-500 focus:outline-none transition">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Slug (Internal Key) *</label>
                <input type="text" name="slug" value="{{ old('slug', $site->slug) }}" required class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-sm text-white font-mono focus:border-emerald-500 focus:outline-none transition">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Subdomain Slug *</label>
                <div class="flex items-center">
                    <input type="text" name="subdomain" value="{{ old('subdomain', $site->subdomain) }}" required class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-l-xl text-sm text-white font-mono focus:border-emerald-500 focus:outline-none transition">
                    <span class="px-3 py-2.5 bg-slate-800 text-slate-400 text-xs border border-l-0 border-slate-800 rounded-r-xl font-mono">.easytsk.com</span>
                </div>
                <p class="text-[10px] text-slate-500 mt-1">Subdomain prefix (e.g. blog1, blog2, blog7)</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Custom Domain / Subdomain URL</label>
                <input type="text" name="domain" value="{{ old('domain', $site->domain) }}" placeholder="e.g. blog1.easytsk.com or https://mycustomblog.com" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-sm text-white font-mono focus:border-emerald-500 focus:outline-none transition">
                <p class="text-[10px] text-emerald-400/80 mt-1">&check; Simply enter your domain URL here and save — no code changes needed!</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Niche / Industry</label>
                <input type="text" name="niche" value="{{ old('niche', $site->niche) }}" placeholder="e.g. Crypto, Finance, Tech" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-sm text-white focus:border-emerald-500 focus:outline-none transition">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Theme Layout Style</label>
                <select name="theme_layout" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-sm text-white focus:border-emerald-500 focus:outline-none transition">
                    <option value="modern" {{ $site->theme_layout === 'modern' ? 'selected' : '' }}>Modern High CTR Magazine</option>
                    <option value="minimal" {{ $site->theme_layout === 'minimal' ? 'selected' : '' }}>Minimalist Clean Reader</option>
                    <option value="bold" {{ $site->theme_layout === 'bold' ? 'selected' : '' }}>Bold Tech Media</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Tagline</label>
                <input type="text" name="tagline" value="{{ old('tagline', $site->tagline) }}" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-sm text-white focus:border-emerald-500 focus:outline-none transition">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Description</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-sm text-white focus:border-emerald-500 focus:outline-none transition">{{ old('description', $site->description) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Theme Accent Color</label>
                <div class="flex items-center gap-3">
                    <input type="color" name="theme_color" value="{{ old('theme_color', $site->theme_color) }}" class="w-10 h-10 rounded-xl cursor-pointer bg-transparent border-0">
                    <input type="text" name="theme_color_text" value="{{ old('theme_color', $site->theme_color) }}" oninput="document.querySelector('input[name=theme_color]').value=this.value" class="w-32 px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-mono text-white">
                </div>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Custom Header Scripts (Popunder / Google Analytics / Adsterra / Monetag head scripts)</label>
                <textarea name="header_scripts" rows="4" class="w-full px-4 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-mono text-emerald-400 focus:border-emerald-500 focus:outline-none transition" placeholder="<script>...</script>">{{ old('header_scripts', $site->header_scripts) }}</textarea>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Custom Footer Scripts</label>
                <textarea name="footer_scripts" rows="3" class="w-full px-4 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-mono text-emerald-400 focus:border-emerald-500 focus:outline-none transition" placeholder="<script>...</script>">{{ old('footer_scripts', $site->footer_scripts) }}</textarea>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
            <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" {{ $site->is_active ? 'checked' : '' }} class="w-4 h-4 rounded bg-slate-900 border-slate-800 text-emerald-500 focus:ring-emerald-500">
                <span>Site is Active</span>
            </label>

            <button type="submit" class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 font-bold text-slate-950 text-xs rounded-xl shadow-lg shadow-emerald-500/20 transition">
                Update Site Settings
            </button>
        </div>
    </form>
</div>
@endsection
