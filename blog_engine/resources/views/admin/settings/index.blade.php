@extends('layouts.admin')

@section('title', 'Site Settings & SEO')
@section('page-title', 'Settings for: ' . ($site ? $site->name : 'Blog'))

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold text-white">Site Branding, Colors & Global SEO</h3>
            <p class="text-xs text-slate-400">Settings applied exclusively to <span class="text-emerald-400 font-semibold">{{ $site->name }}</span> ({{ $site->subdomain }}.easytsk.com)</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-xs text-slate-400 hover:text-white">&larr; Back to Dashboard</a>
    </div>

    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="bg-slate-950/80 border border-slate-800/80 rounded-2xl p-6 sm:p-8 space-y-8">
        @csrf

        <!-- Branding Section -->
        <div class="space-y-4">
            <h4 class="text-sm font-bold text-white border-b border-slate-800 pb-2">Branding & Identity</h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Site Title *</label>
                    <input type="text" name="name" value="{{ old('name', $site->name) }}" required class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Tagline</label>
                    <input type="text" name="tagline" value="{{ old('tagline', $site->tagline) }}" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-300 mb-1">About / Site Description</label>
                    <textarea name="description" rows="2" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">{{ old('description', $site->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Theme Accent Color</label>
                    <div class="flex items-center gap-3">
                        <input type="color" name="theme_color" value="{{ old('theme_color', $site->theme_color) }}" class="w-9 h-9 rounded-xl cursor-pointer bg-transparent border-0">
                        <input type="text" name="theme_color_text" value="{{ old('theme_color', $site->theme_color) }}" oninput="document.querySelector('input[name=theme_color]').value=this.value" class="w-28 px-3 py-1.5 bg-slate-900 border border-slate-800 rounded-xl text-xs font-mono text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Theme Layout Style</label>
                    <select name="theme_layout" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
                        <option value="modern" {{ $site->theme_layout === 'modern' ? 'selected' : '' }}>Modern High CTR Magazine</option>
                        <option value="minimal" {{ $site->theme_layout === 'minimal' ? 'selected' : '' }}>Minimalist Clean Reader</option>
                        <option value="bold" {{ $site->theme_layout === 'bold' ? 'selected' : '' }}>Bold Tech Media</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Logo Image</label>
                    @if($site->logo)
                        <img src="{{ $site->logo }}" alt="" class="h-8 mb-2 object-contain bg-slate-900 p-1 rounded">
                    @endif
                    <input type="file" name="logo" accept="image/*" class="block w-full text-xs text-slate-400 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:bg-slate-800 file:text-slate-200">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Favicon (.ico / .png)</label>
                    @if($site->favicon)
                        <img src="{{ $site->favicon }}" alt="" class="w-6 h-6 mb-2">
                    @endif
                    <input type="file" name="favicon" accept="image/x-icon,image/png" class="block w-full text-xs text-slate-400 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:bg-slate-800 file:text-slate-200">
                </div>
            </div>
        </div>

        <!-- Global SEO Defaults Section -->
        <div class="space-y-4">
            <h4 class="text-sm font-bold text-white border-b border-slate-800 pb-2">Default Search Engine Optimization (SEO)</h4>

            @php
                $seo = $site->seo_defaults ?? [];
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Default Meta Title Template</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title', $seo['meta_title'] ?? '') }}" placeholder="e.g. CryptoPulse - Latest Cryptocurrency News & Insights" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Default Meta Description</label>
                    <textarea name="meta_description" rows="2" placeholder="Search snippet displayed on Google for the homepage..." class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">{{ old('meta_description', $seo['meta_description'] ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Focus Keywords (comma separated)</label>
                    <input type="text" name="keywords" value="{{ old('keywords', $seo['keywords'] ?? '') }}" placeholder="crypto, bitcoin, trading, fintech" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Google Analytics Measurement ID</label>
                    <input type="text" name="google_analytics_id" value="{{ old('google_analytics_id', $seo['google_analytics_id'] ?? '') }}" placeholder="G-XXXXXXXXXX" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none font-mono">
                </div>
            </div>
        </div>

        <!-- Social Links Section -->
        <div class="space-y-4">
            <h4 class="text-sm font-bold text-white border-b border-slate-800 pb-2">Social Channels</h4>

            @php
                $social = $site->social_links ?? [];
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Twitter / X URL</label>
                    <input type="url" name="twitter_url" value="{{ old('twitter_url', $social['twitter'] ?? '') }}" placeholder="https://x.com/..." class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Telegram Channel URL</label>
                    <input type="url" name="telegram_url" value="{{ old('telegram_url', $social['telegram'] ?? '') }}" placeholder="https://t.me/..." class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Facebook Page URL</label>
                    <input type="url" name="facebook_url" value="{{ old('facebook_url', $social['facebook'] ?? '') }}" placeholder="https://facebook.com/..." class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">YouTube Channel URL</label>
                    <input type="url" name="youtube_url" value="{{ old('youtube_url', $social['youtube'] ?? '') }}" placeholder="https://youtube.com/..." class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Task Reward Engine & Anti-Cheat AdBlock Section -->
        <div class="space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                <h4 class="text-sm font-bold text-white flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                    <span>EasyTSK Task Reward & Anti-Cheat AdBlocker</span>
                </h4>
                <span class="text-[10px] text-amber-400 font-mono font-bold uppercase bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/30">Microtask Engine</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Required Dwell Time (Seconds) *</label>
                    <input type="number" name="task_timer_seconds" min="10" max="300" value="{{ old('task_timer_seconds', $site->task_timer_seconds ?: 60) }}" required class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-mono text-white focus:border-amber-500 focus:outline-none">
                    <p class="text-[10px] text-slate-400 mt-1">Default is 60 seconds (1 minute).</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Fixed Secret Code (Optional)</label>
                    <input type="text" name="fixed_secret_code" value="{{ old('fixed_secret_code', $site->fixed_secret_code) }}" placeholder="e.g. CRYPTO2026 (Leave blank for dynamic)" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-mono text-emerald-300 focus:border-emerald-500 focus:outline-none">
                    <p class="text-[10px] text-slate-400 mt-1">If blank, generates unique one-time codes.</p>
                </div>

                <div class="flex flex-col justify-center">
                    <label class="flex items-center gap-2 cursor-pointer pt-2">
                        <input type="checkbox" name="task_reward_enabled" value="1" {{ $site->task_reward_enabled ? 'checked' : '' }} class="w-4 h-4 rounded bg-slate-900 border-slate-800 text-emerald-500 focus:ring-emerald-500">
                        <span class="text-xs font-bold text-slate-200">Enable Task Reward Timer</span>
                    </label>
                    <p class="text-[10px] text-slate-400 mt-0.5">Shows countdown bar & code box.</p>
                </div>

                <div class="flex flex-col justify-center">
                    <label class="flex items-center gap-2 cursor-pointer pt-2">
                        <input type="checkbox" name="adblock_detection_enabled" value="1" {{ $site->adblock_detection_enabled ? 'checked' : '' }} class="w-4 h-4 rounded bg-slate-900 border-slate-800 text-rose-500 focus:ring-rose-500">
                        <span class="text-xs font-bold text-slate-200">Strict AdBlocker Enforcer</span>
                    </label>
                    <p class="text-[10px] text-slate-400 mt-0.5">Freezes timer until AdBlock is off.</p>
                </div>
            </div>
        </div>

        <!-- Custom Header & Footer Scripts -->
        <div class="space-y-4">
            <h4 class="text-sm font-bold text-white border-b border-slate-800 pb-2">Custom Code Injection</h4>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Header Scripts (Injected right before &lt;/head&gt;)</label>
                    <textarea name="header_scripts" rows="3" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-mono text-emerald-400 focus:border-emerald-500 focus:outline-none" placeholder="<script>...</script>">{{ old('header_scripts', $site->header_scripts) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Footer Scripts (Injected right before &lt;/body&gt;)</label>
                    <textarea name="footer_scripts" rows="3" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-mono text-emerald-400 focus:border-emerald-500 focus:outline-none" placeholder="<script>...</script>">{{ old('footer_scripts', $site->footer_scripts) }}</textarea>
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-800 flex justify-end">
            <button type="submit" class="px-8 py-3 bg-emerald-500 hover:bg-emerald-600 font-bold text-slate-950 text-xs rounded-xl shadow-lg shadow-emerald-500/20 transition">
                Save Site Settings
            </button>
        </div>
    </form>
</div>
@endsection
