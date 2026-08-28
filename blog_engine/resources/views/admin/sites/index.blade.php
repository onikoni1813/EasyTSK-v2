@extends('layouts.admin')

@section('title', 'Manage Blog Sites')
@section('page-title', 'Site Manager (Blog 01 - 08+)')

@section('content')
<div class="space-y-6">
    <!-- Action Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-lg font-bold text-white">All Multi-Tenant Sites</h3>
            <p class="text-xs text-slate-400">Each site runs independently on its own subdomain with full isolation.</p>
        </div>
        <a href="{{ route('admin.sites.create') }}" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold text-xs rounded-xl shadow-lg shadow-emerald-500/20 flex items-center gap-1.5 transition self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>Add New Blog Site</span>
        </a>
    </div>

    <!-- Sites Table -->
    <div class="bg-slate-950/80 border border-slate-800/80 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto w-full">
            <table class="w-full min-w-[650px] text-left text-xs text-slate-300">
                <thead class="bg-slate-900/90 text-slate-400 uppercase text-[10px] font-bold tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Site Name & Niche</th>
                        <th class="px-6 py-4">Subdomain / Domain</th>
                        <th class="px-6 py-4 text-center">Theme Color</th>
                        <th class="px-6 py-4 text-center">Articles</th>
                        <th class="px-6 py-4 text-center">Ad Slots</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($sites as $site)
                        @php
                            $isActiveSite = $currentSite && $currentSite->id === $site->id;
                        @endphp
                        <tr class="hover:bg-slate-900/40 transition {{ $isActiveSite ? 'bg-emerald-500/5' : '' }}">
                            <td class="px-6 py-4">
                                <div class="font-bold text-white flex items-center gap-2">
                                    <span>{{ $site->name }}</span>
                                    @if($isActiveSite)
                                        <span class="px-2 py-0.5 rounded text-[9px] font-extrabold uppercase bg-emerald-500 text-slate-950">Active</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">{{ $site->niche ?: 'General' }} &bull; {{ $site->tagline }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono text-emerald-400 text-[11px]">
                                <div>{{ $site->subdomain }}.easytsk.com</div>
                                @if($site->domain)
                                    <div class="text-slate-400 text-[10px]">{{ $site->domain }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-slate-900 border border-slate-800">
                                    <span class="w-3 h-3 rounded-full shadow" style="background-color: {{ $site->theme_color }}"></span>
                                    <span class="font-mono text-[10px] text-slate-300">{{ $site->theme_color }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-slate-200">
                                {{ $site->posts_count }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 text-amber-300 border border-amber-500/20">
                                    {{ $site->ad_placements_count }} Slots
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $site->is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/10 text-rose-400 border border-rose-500/30' }}">
                                    {{ $site->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.switch-site', $site->id) }}" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-[11px] font-semibold transition" title="Switch to this site">
                                        Select
                                    </a>
                                    <a href="{{ route('home', ['site' => $site->id]) }}" target="_blank" class="p-1.5 bg-slate-800 hover:bg-slate-700 text-emerald-400 rounded-lg transition" title="View Frontend">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                    </a>
                                    <a href="{{ route('admin.sites.edit', $site->id) }}" class="p-1.5 bg-slate-800 hover:bg-slate-700 text-blue-400 rounded-lg transition" title="Edit Site">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </a>
                                    <form action="{{ route('admin.sites.destroy', $site->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete {{ $site->name }} and all its posts?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 bg-slate-800 hover:bg-rose-900/50 text-rose-400 rounded-lg transition" title="Delete Site">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                No blog sites created yet. Click "+ Add New Blog Site" to create your first site (e.g. Blog 01).
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
