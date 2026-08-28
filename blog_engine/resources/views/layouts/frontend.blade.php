@inject('adEngine', 'App\Services\AdEngine')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    
    <!-- Dynamic SEO Meta Tags -->
    <title>{{ $seo['title'] ?? ($currentSite->name ?? 'Blog') }}</title>
    <meta name="description" content="{{ $seo['description'] ?? ($currentSite->description ?? '') }}">
    @if(!empty($seo['canonical']))
        <link rel="canonical" href="{{ $seo['canonical'] }}">
    @endif

    <!-- OpenGraph & Twitter Cards -->
    <meta property="og:title" content="{{ $seo['title'] ?? ($currentSite->name ?? 'Blog') }}">
    <meta property="og:description" content="{{ $seo['description'] ?? ($currentSite->description ?? '') }}">
    <meta property="og:type" content="{{ $seo['og_type'] ?? 'website' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $currentSite->name ?? 'Blog' }}">
    @if(!empty($seo['og_image']))
        <meta property="og:image" content="{{ $seo['og_image'] }}">
        <meta name="twitter:image" content="{{ $seo['og_image'] }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['title'] ?? ($currentSite->name ?? 'Blog') }}">
    <meta name="twitter:description" content="{{ $seo['description'] ?? ($currentSite->description ?? '') }}">

    <!-- Favicon -->
    @if($currentSite?->favicon)
        <link rel="icon" href="{{ $currentSite->favicon }}">
    @endif

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,700;1,600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    @php
        $accentColor = $currentSite?->theme_color ?: '#2563eb';
    @endphp

    <style>
        :root {
            --brand-color: {{ $accentColor }};
        }
        body { 
            font-family: 'Inter', sans-serif; 
            overflow-x: hidden;
        }
        .font-serif-title { font-family: 'Playfair Display', serif; }
        .brand-bg { background-color: var(--brand-color); }
        .brand-text { color: var(--brand-color); }
        .brand-border { border-color: var(--brand-color); }
        .brand-glow { box-shadow: 0 4px 20px -2px {{ $accentColor }}40; }

        /* Responsive Article Content */
        .article-content {
            font-size: 1rem;
            line-height: 1.75;
            color: #334155;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        @media (min-width: 640px) {
            .article-content { font-size: 1.0625rem; line-height: 1.8; }
        }
        .article-content p { margin-bottom: 1.25rem; }
        .article-content h2 { 
            font-size: 1.35rem; 
            font-weight: 800; 
            margin-top: 1.75rem; 
            margin-bottom: 0.75rem; 
            color: #0f172a; 
            line-height: 1.3;
        }
        @media (min-width: 640px) {
            .article-content h2 { font-size: 1.65rem; margin-top: 2rem; margin-bottom: 1rem; }
        }
        .article-content h3 { 
            font-size: 1.15rem; 
            font-weight: 700; 
            margin-top: 1.25rem; 
            margin-bottom: 0.5rem; 
            color: #1e293b; 
        }
        @media (min-width: 640px) {
            .article-content h3 { font-size: 1.35rem; margin-top: 1.5rem; }
        }
        .article-content ul, .article-content ol { margin-bottom: 1.25rem; padding-left: 1.25rem; }
        .article-content ul { list-style-type: disc; }
        .article-content ol { list-style-type: decimal; }
        .article-content li { margin-bottom: 0.4rem; color: #334155; }
        .article-content blockquote { 
            border-left: 4px solid var(--brand-color); 
            padding-left: 1rem; 
            font-style: italic; 
            color: #475569; 
            margin: 1.25rem 0; 
            background: #f8fafc; 
            padding-top: 0.75rem; 
            padding-bottom: 0.75rem; 
            border-radius: 0 0.75rem 0.75rem 0; 
        }
        .article-content a { color: var(--brand-color); text-decoration: underline; font-weight: 500; }
        .article-content img { border-radius: 0.75rem; margin: 1.25rem auto; max-width: 100%; height: auto; display: block; }
        .article-content pre, .article-content code { max-width: 100%; overflow-x: auto; }

        /* Responsive Ad Banners (Prevent Mobile Horizontal Scroll) */
        .ad-container, .ad-in-content {
            max-width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .ad-container iframe, .ad-container img, .ad-container ins,
        .ad-in-content iframe, .ad-in-content img, .ad-in-content ins {
            max-width: 100% !important;
            height: auto !important;
        }
    </style>

    <!-- JSON-LD Structured Data Schema -->
    @if(!empty($schema))
        <script type="application/ld+json">
            {!! $schema !!}
        </script>
    @endif

    <!-- Custom Header Scripts / Popunder / Analytics -->
    @if(!empty($currentSite?->header_scripts))
        {!! $currentSite->header_scripts !!}
    @endif

    <!-- Popunder Slot Injection -->
    @if($adEngine->has('popunder'))
        {!! $adEngine->render('popunder', 'hidden') !!}
    @endif
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col antialiased selection:bg-slate-900 selection:text-white">

    <!-- Top Announcement Bar -->
    @if($currentSite?->niche)
        <div class="bg-slate-900 text-slate-300 text-xs py-1.5 px-4 border-b border-slate-800">
            <div class="max-w-7xl mx-auto flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 truncate">
                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider text-white flex-shrink-0" style="background-color: var(--brand-color)">{{ $currentSite->niche }}</span>
                    <span class="text-slate-400 text-[11px] truncate">{{ $currentSite->tagline ?: 'Latest News & Analysis' }}</span>
                </div>
                <div class="hidden sm:flex items-center gap-4 text-[11px] flex-shrink-0">
                    <span class="text-slate-400 font-mono">{{ date('M j, Y') }}</span>
                    @if(!empty($currentSite->social_links['twitter']))
                        <a href="{{ $currentSite->social_links['twitter'] }}" target="_blank" class="hover:text-white transition">Twitter</a>
                    @endif
                    @if(!empty($currentSite->social_links['telegram']))
                        <a href="{{ $currentSite->social_links['telegram'] }}" target="_blank" class="hover:text-white transition">Telegram</a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Main Header -->
    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-30 shadow-sm/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="h-16 sm:h-20 flex items-center justify-between gap-4">
                <!-- Site Brand / Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 sm:gap-3 group min-w-0">
                    @if($currentSite?->logo)
                        <img src="{{ $currentSite->logo }}" alt="{{ $currentSite->name }}" class="h-8 sm:h-10 w-auto object-contain flex-shrink-0">
                    @else
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center font-black text-white text-base sm:text-xl shadow-md transition group-hover:scale-105 flex-shrink-0" style="background-color: var(--brand-color)">
                            {{ substr($currentSite?->name ?? 'B', 0, 1) }}
                        </div>
                    @endif
                    <div class="truncate">
                        <span class="text-base sm:text-2xl font-black tracking-tight text-slate-900 group-hover:text-slate-700 transition block leading-none truncate">
                            {{ $currentSite?->name ?? 'EasyTSK Blog' }}
                        </span>
                        <span class="text-[9px] sm:text-[10px] uppercase font-bold tracking-widest text-slate-600 block mt-0.5 sm:mt-1 truncate">
                            {{ $currentSite?->niche ?? 'Magazine' }}
                        </span>
                    </div>
                </a>

                @php
                    $navCategories = \App\Models\Category::orderBy('sort_order')->take(6)->get();
                @endphp

                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex items-center gap-5 lg:gap-6 text-xs lg:text-sm font-semibold text-slate-600">
                    <a href="{{ route('home') }}" class="hover:text-slate-900 transition {{ request()->routeIs('home') ? 'text-slate-900 font-bold' : '' }}">Home</a>
                    
                    @foreach($navCategories as $navCat)
                        <a href="{{ route('category.show', $navCat->slug) }}" class="hover:text-slate-900 transition">
                            {{ $navCat->name }}
                        </a>
                    @endforeach

                    <a href="{{ route('page.show', 'about-us') }}" class="hover:text-slate-900 transition">About</a>
                    <a href="{{ route('page.show', 'contact') }}" class="hover:text-slate-900 transition">Contact</a>
                </nav>

                <!-- Right Actions: Desktop Sitemap + Mobile Hamburger -->
                <div class="flex items-center gap-2">
                    <a href="{{ route('sitemap') }}" target="_blank" class="hidden sm:flex px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-700 transition items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 5c7.18 0 13 5.82 13 13M6 11a7 7 0 017 7m-6 0a1 1 0 11-2 0 1 1 0 012 0z"></path></svg>
                        <span>Sitemap</span>
                    </a>

                    <!-- Mobile Menu Hamburger Button -->
                    <button type="button" onclick="toggleMobileMenu()" class="md:hidden p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition" aria-label="Open Navigation Menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Drawer / Dropdown -->
        <div id="mobileMenu" class="hidden md:hidden bg-white border-b border-slate-200 px-4 py-5 space-y-4 shadow-xl">
            <div class="space-y-1">
                <a href="{{ route('home') }}" class="block px-3 py-2 rounded-lg text-sm font-bold {{ request()->routeIs('home') ? 'bg-slate-100 text-slate-900' : 'text-slate-700 hover:bg-slate-50' }}">
                    Home
                </a>

                <div class="pt-2 pb-1 px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-600">Categories</div>
                @foreach($navCategories as $navCat)
                    <a href="{{ route('category.show', $navCat->slug) }}" class="block px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                        &bull; {{ $navCat->name }}
                    </a>
                @endforeach

                <div class="pt-3 border-t border-slate-100 space-y-1">
                    <a href="{{ route('page.show', 'about-us') }}" class="block px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-50">About Us</a>
                    <a href="{{ route('page.show', 'contact') }}" class="block px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-50">Contact Us</a>
                    <a href="{{ route('page.show', 'privacy-policy') }}" class="block px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-50">Privacy Policy</a>
                    <a href="{{ route('sitemap') }}" target="_blank" class="block px-3 py-1.5 rounded-lg text-xs font-semibold text-amber-600 hover:bg-slate-50">XML Sitemap</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Top Billboard Ad Slot (Header 728x90) -->
    @if($adEngine->has('header'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 sm:pt-6">
            {!! $adEngine->render('header', 'mx-auto text-center') !!}
        </div>
    @endif

    <!-- Main Dynamic Content Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        @yield('content')
    </main>

    <!-- Footer Banner Ad Slot -->
    @if($adEngine->has('footer'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-4 sm:pb-6">
            {!! $adEngine->render('footer', 'mx-auto text-center') !!}
        </div>
    @endif

    <!-- Main Footer -->
    <footer class="bg-slate-950 text-slate-400 border-t border-slate-800/80 pt-12 sm:pt-16 pb-10 sm:pb-12 mt-12 sm:mt-16 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8 sm:gap-10 pb-10 sm:pb-12 border-b border-slate-800">
                <!-- Column 1: Brand -->
                <div class="sm:col-span-2 space-y-3 sm:space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center font-extrabold text-white text-base" style="background-color: var(--brand-color)">
                            {{ substr($currentSite?->name ?? 'B', 0, 1) }}
                        </div>
                        <span class="text-xl font-bold text-white tracking-tight">{{ $currentSite?->name ?? 'EasyTSK Blog' }}</span>
                    </div>
                    <p class="text-slate-400 text-xs leading-relaxed max-w-md">
                        {{ $currentSite?->description ?: ($currentSite?->name . ' is your trusted digital publication for curated editorial insights, daily breaking news, and in-depth educational tutorials.') }}
                    </p>
                </div>

                <!-- Column 2: Categories -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-200 mb-3 sm:mb-4">Topics</h4>
                    <ul class="space-y-2 text-xs">
                        @foreach($navCategories as $cat)
                            <li>
                                <a href="{{ route('category.show', $cat->slug) }}" class="hover:text-white transition">{{ $cat->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Column 3: Legal & Publisher Policy -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-200 mb-3 sm:mb-4">Publisher Policy</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="{{ route('page.show', 'privacy-policy') }}" class="hover:text-white transition">Privacy Policy</a></li>
                        <li><a href="{{ route('page.show', 'terms-of-service') }}" class="hover:text-white transition">Terms of Service</a></li>
                        <li><a href="{{ route('page.show', 'about-us') }}" class="hover:text-white transition">About Us</a></li>
                        <li><a href="{{ route('page.show', 'contact') }}" class="hover:text-white transition">Contact Us</a></li>
                        <li><a href="{{ route('robots') }}" target="_blank" class="hover:text-white transition">Robots.txt</a></li>
                    </ul>
                </div>
            </div>

            <div class="pt-6 sm:pt-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px] text-slate-400 text-center sm:text-left">
                <div>
                    &copy; {{ date('Y') }} {{ $currentSite?->name ?? 'Blog' }}. All rights reserved.
                </div>
                <div>
                    Built for High-Yield Publisher Monetization.
                </div>
            </div>
        </div>
    </footer>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }
    </script>

    @php
        $adblockEnabled = $currentSite?->adblock_detection_enabled ?? true;
    @endphp

    @if($adblockEnabled)
        <!-- Static Bait DOM element trap (Catches CSS injection & cosmetic filtering) -->
        <div id="ad-bait-element" class="adsbox banner-ad ad-banner pub_300x250 pub_728x90 text-ad ad-zone ad_unit" style="position: absolute !important; left: -9999px !important; top: -9999px !important; width: 300px !important; height: 250px !important; pointer-events: none !important;" aria-hidden="true">&nbsp;</div>

        <!-- Strict AdBlocker Fullscreen Blocking Modal -->
        <div id="globalAdblockOverlay" class="fixed inset-0 bg-slate-950/95 backdrop-blur-xl z-50 flex items-center justify-center p-4 hidden">
            <div class="max-w-md w-full bg-slate-900 border-2 border-rose-500/60 rounded-3xl p-6 sm:p-8 text-center space-y-5 shadow-2xl shadow-rose-500/20">
                <div class="w-16 h-16 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-500 flex items-center justify-center mx-auto animate-pulse">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                </div>

                <div class="space-y-2">
                    <h3 class="text-xl font-black text-white tracking-tight">AdBlocker Detected!</h3>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        We detected an active <span class="text-rose-400 font-bold">AdBlocker</span>, <span class="text-rose-400 font-bold">uBlock</span>, or <span class="text-rose-400 font-bold">Brave Shield</span>. Our free publication and reward tasks require advertisements to function.
                    </p>
                </div>

                <div class="p-3 bg-slate-950 rounded-xl border border-slate-800 text-[11px] text-amber-300 font-medium">
                    ⚠️ Please disable your AdBlocker on this domain and reload the page to continue.
                </div>

                <div>
                    <button type="button" onclick="location.reload()" class="w-full py-3 bg-rose-500 hover:bg-rose-600 text-white font-bold text-xs rounded-xl shadow-lg shadow-rose-500/25 transition">
                        I Have Disabled AdBlock &mdash; Reload Page
                    </button>
                </div>
            </div>
        </div>

        <!-- EasyList Rule Bait Script (Blocked by all Basic & Standard Adblockers) -->
        <script src="{{ asset('js/advertisement.js') }}"></script>

        <!-- High-Precision Anti-AdBlock Detection Suite -->
        <script src="{{ asset('js/anti_adblock.js') }}"></script>
    @endif

    <!-- Custom Footer Scripts -->
    @if(!empty($currentSite?->footer_scripts))
        {!! $currentSite->footer_scripts !!}
    @endif
</body>
</html>
