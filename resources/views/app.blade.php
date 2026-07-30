<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <!-- PWA & Dynamic Favicon / Logo -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#02040a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ \App\Models\AppSetting::getByKey('site_name', 'Easytsk V2') }}">
    <link rel="icon" href="{{ \App\Models\AppSetting::getByKey('site_favicon', '/favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ \App\Models\AppSetting::getByKey('site_logo', \App\Models\AppSetting::getByKey('site_favicon', '/favicon.ico')) }}">

    <title>Easytsk V2 | Earn Money Online with EasyTSK & Offers</title>
    <meta name="description"
        content="Easytsk V2 is a trusted online micro-task platform where users complete simple offers, surveys, and micro-tasks to earn real rewards and cash payouts via bKash and Nagad.">
    <meta name="keywords"
        content="earn money online, microtasks, online survey jobs, work from home, bKash cashout, easytsk, online earning platform">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta name="author" content="Easytsk V2">

    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Easytsk V2 | Earn Money Online EasyTSK & Offers">
    <meta property="og:description"
        content="Complete simple online tasks, earn points, and withdraw cash to your mobile wallet. 100% free to join.">
    <meta property="og:site_name" content="Easytsk V2">
    <meta property="og:image" content="{{ \App\Models\AppSetting::getByKey('site_logo', asset('/favicon.ico')) }}">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Easytsk V2 | Earn Money Online with EasyTSK">
    <meta name="twitter:description" content="Complete simple tasks and earn rewards online with Easytsk V2.">
    <meta name="twitter:image" content="{{ \App\Models\AppSetting::getByKey('site_logo', asset('/favicon.ico')) }}">

    <!-- Google Fonts Optimized Asynchronous Loading -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    </noscript>

    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').then(function(reg) {
                    console.log('PWA ServiceWorker ready: ', reg.scope);
                }).catch(function(err) {
                    console.log('PWA ServiceWorker error: ', err);
                });
            });
        }
    </script>

    <!-- Scripts and Styles -->
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body
    class="font-sans antialiased bg-slate-950 text-slate-100 min-h-screen overflow-x-hidden selection:bg-indigo-500 selection:text-white">
    @inertia
</body>

</html>