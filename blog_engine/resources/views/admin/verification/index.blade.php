@extends('layouts.admin')

@section('title', 'Site Verification & ads.txt')
@section('page-title', 'Verification & ads.txt: ' . ($site ? $site->name : 'Blog'))

@section('content')
<div class="max-w-5xl mx-auto space-y-8">
    <!-- Header Banner -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-emerald-500/10 via-slate-900 to-slate-950 border border-emerald-500/30 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-500 text-slate-950">Publisher Ownership</span>
                <span class="text-xs text-emerald-400 font-mono">Site: {{ $site->name }}</span>
            </div>
            <h3 class="text-lg font-bold text-white">ads.txt & Root Verification Files</h3>
            <p class="text-xs text-slate-400 mt-1">Manage network verification files, Service Workers (sw.js for Monetag), and ads.txt records.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ url('/ads.txt?site=' . $site->id) }}" target="_blank" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-emerald-400 text-xs font-semibold rounded-lg border border-slate-700 flex items-center gap-1.5 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                <span>View Live ads.txt</span>
            </a>
        </div>
    </div>

    <!-- Section 1: ads.txt Management -->
    <div class="bg-slate-950/80 border border-slate-800/80 rounded-2xl p-6 space-y-4">
        <div class="border-b border-slate-800 pb-3">
            <h4 class="text-sm font-bold text-white flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                <span>Subdomain ads.txt Manager</span>
            </h4>
            <p class="text-xs text-slate-400 mt-0.5">Paste authorization lines provided by Google AdSense, Adsterra, Monetag, or PropellerAds.</p>
        </div>

        <form action="{{ route('admin.verification.ads-txt') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <textarea name="ads_txt" rows="6" placeholder="e.g.&#10;google.com, pub-XXXXXXXXXXXXXXXX, DIRECT, f08c47fec0942fa0&#10;adsterra.com, DIRECT, XXXXXXXXXXXXX" class="w-full px-4 py-3 bg-slate-900 border border-slate-800 rounded-xl text-xs font-mono text-emerald-300 placeholder-slate-600 focus:border-emerald-500 focus:outline-none leading-relaxed">{{ $site->ads_txt }}</textarea>
            </div>

            <div class="flex items-center justify-between">
                <div class="text-[11px] text-slate-400">
                    Live URL: <code class="text-emerald-400 font-mono">{{ $site->subdomain }}.easytsk.com/ads.txt</code>
                </div>
                <button type="submit" class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 font-bold text-slate-950 text-xs rounded-xl shadow-lg shadow-emerald-500/20 transition">
                    Save ads.txt
                </button>
            </div>
        </form>
    </div>

    <!-- Section 2: Root Verification Files (sw.js, HTML/TXT verification) -->
    <div class="bg-slate-950/80 border border-slate-800/80 rounded-2xl p-6 space-y-6">
        <div class="border-b border-slate-800 pb-3">
            <h4 class="text-sm font-bold text-white flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-teal-400"></span>
                <span>Root Verification Files & Service Workers</span>
            </h4>
            <p class="text-xs text-slate-400 mt-0.5">Add custom verification files required by Monetag (<code class="text-emerald-400">sw.js</code>), Google Search Console, Yandex, or Adsterra without FTP/cPanel file access.</p>
        </div>

        <!-- Add Root File Form -->
        <form action="{{ route('admin.verification.root-files.store') }}" method="POST" class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">File Name (Root URL):</label>
                    <input type="text" name="filename" required placeholder="e.g. sw.js or monetag_12345.html" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-xs font-mono text-slate-200 focus:border-teal-400 focus:outline-none">
                    <p class="text-[10px] text-slate-400 mt-1">Example: <code class="text-teal-400">sw.js</code> will serve at <code class="text-teal-400">{{ $site->subdomain }}.easytsk.com/sw.js</code></p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">MIME / Content-Type:</label>
                    <select name="mime_type" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-xs text-slate-200 focus:border-teal-400 focus:outline-none">
                        <option value="text/javascript">JavaScript (text/javascript) — for sw.js</option>
                        <option value="text/html">HTML (text/html) — for verification pages</option>
                        <option value="text/plain">Plain Text (text/plain)</option>
                        <option value="application/json">JSON (application/json)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">File Content / Code:</label>
                <textarea name="content" rows="4" required placeholder="Paste the script code (e.g. Monetag Service Worker code) or verification string here..." class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-xs font-mono text-teal-300 placeholder-slate-600 focus:border-teal-400 focus:outline-none leading-relaxed"></textarea>
            </div>

            <div class="text-right">
                <button type="submit" class="px-6 py-2 bg-teal-500 hover:bg-teal-400 text-slate-950 font-bold text-xs rounded-xl shadow-lg shadow-teal-500/20 transition">
                    + Add / Update Root File
                </button>
            </div>
        </form>

        <!-- Active Root Files Table -->
        <div class="overflow-x-auto w-full">
            <table class="w-full min-w-[550px] text-left text-xs text-slate-300">
                <thead class="bg-slate-900/90 text-slate-400 uppercase text-[10px] font-bold tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">File Name</th>
                        <th class="px-4 py-3">MIME Type</th>
                        <th class="px-4 py-3">Direct URL</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($rootFiles as $rf)
                        <tr class="hover:bg-slate-900/40 transition">
                            <td class="px-4 py-3 font-mono font-bold text-white">
                                {{ $rf->filename }}
                            </td>
                            <td class="px-4 py-3 text-slate-400">
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-[10px] font-mono">{{ $rf->mime_type }}</span>
                            </td>
                            <td class="px-4 py-3 font-mono text-emerald-400">
                                <a href="{{ url('/' . $rf->filename . '?site=' . $site->id) }}" target="_blank" class="hover:underline flex items-center gap-1">
                                    <span>/{{ $rf->filename }}</span>
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                </a>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form action="{{ route('admin.verification.root-files.destroy', $rf->id) }}" method="POST" onsubmit="return confirm('Delete this verification file?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 bg-slate-800 hover:bg-rose-900/50 text-rose-400 rounded-lg transition" title="Delete">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-400">
                                No custom root files configured yet. You can add sw.js, monetag.html, or Google verification files above.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
