@extends('layouts.admin')

@section('title', 'Authors')
@section('page-title', 'Author Profiles')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Add Author Form -->
    <div class="p-6 rounded-2xl bg-slate-950/80 border border-slate-800/80 space-y-4">
        <h3 class="text-sm font-bold text-white">Add New Author</h3>

        <form action="{{ route('admin.authors.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Author Name *</label>
                <input type="text" name="name" required placeholder="e.g. Satoshi Nakamoto" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Email</label>
                <input type="email" name="email" placeholder="author@domain.com" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Bio / About Author</label>
                <textarea name="bio" rows="3" placeholder="Senior Analyst with 10+ years in Fintech..." class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Avatar Image</label>
                <input type="file" name="avatar" accept="image/*" class="block w-full text-xs text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-slate-800 file:text-slate-200">
            </div>

            <button type="submit" class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-600 font-bold text-slate-950 text-xs rounded-xl shadow-lg shadow-emerald-500/20 transition">
                Create Author Profile
            </button>
        </form>
    </div>

    <!-- Authors List -->
    <div class="lg:col-span-2 bg-slate-950/80 border border-slate-800/80 rounded-2xl overflow-hidden shadow-xl">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <h4 class="text-xs font-bold uppercase text-slate-400">Authors ({{ $authors->count() }})</h4>
        </div>

        <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-900/90 text-slate-400 uppercase text-[10px] font-bold tracking-wider border-b border-slate-800">
                <tr>
                    <th class="px-6 py-3">Author</th>
                    <th class="px-6 py-3">Bio</th>
                    <th class="px-6 py-3 text-center">Articles</th>
                    <th class="px-6 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($authors as $author)
                    <tr class="hover:bg-slate-900/40 transition">
                        <td class="px-6 py-3.5">
                            <div class="flex items-center gap-3">
                                @if($author->avatar)
                                    <img src="{{ $author->avatar }}" alt="" class="w-8 h-8 rounded-full object-cover">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center font-bold text-emerald-400 text-xs">
                                        {{ substr($author->name, 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="font-bold text-white">{{ $author->name }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $author->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3.5 text-slate-400 max-w-xs truncate">
                            {{ $author->bio ?: 'No bio added.' }}
                        </td>
                        <td class="px-6 py-3.5 text-center font-bold text-emerald-400">
                            {{ $author->posts_count }}
                        </td>
                        <td class="px-6 py-3.5 text-right">
                            <form action="{{ route('admin.authors.destroy', $author->id) }}" method="POST" onsubmit="return confirm('Delete this author?');" class="inline">
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
                        <td colspan="4" class="px-6 py-8 text-center text-slate-500">
                            No authors created yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
