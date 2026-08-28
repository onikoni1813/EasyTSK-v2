<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Engine Setup - EasyTSK</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-6 antialiased">
    <div class="max-w-md w-full text-center space-y-6 bg-slate-900 border border-slate-800 p-8 rounded-3xl shadow-2xl">
        <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-extrabold text-2xl flex items-center justify-center mx-auto">
            B
        </div>

        <div class="space-y-2">
            <h1 class="text-xl font-bold text-white">Multi-Site Blog Engine Ready</h1>
            <p class="text-xs text-slate-400">No blog site has been configured or activated yet.</p>
        </div>

        <a href="{{ route('admin.login') }}" class="block w-full py-3 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold text-xs rounded-xl shadow-lg shadow-emerald-500/25 transition">
            Login to Admin Panel to Create Blog 01
        </a>
    </div>
</body>
</html>
