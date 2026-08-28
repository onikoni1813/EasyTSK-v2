@extends('layouts.admin')

@section('title', 'Categories')
@section('page-title', 'Categories for: ' . ($site ? $site->name : 'Blog'))

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column: Add Category Form -->
    <div class="p-6 rounded-2xl bg-slate-950/80 border border-slate-800/80 space-y-4">
        <h3 class="text-sm font-bold text-white">Add New Category</h3>

        <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Category Name *</label>
                <input type="text" name="name" required placeholder="e.g. Cryptocurrency" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Slug (Optional)</label>
                <input type="text" name="slug" placeholder="cryptocurrency" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white font-mono focus:border-emerald-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Description</label>
                <textarea name="description" rows="3" placeholder="Category summary for SEO archives..." class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Sort Order</label>
                <input type="number" name="sort_order" value="0" class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-emerald-500 focus:outline-none">
            </div>

            <button type="submit" class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-600 font-bold text-slate-950 text-xs rounded-xl shadow-lg shadow-emerald-500/20 transition">
                Create Category
            </button>
        </form>
    </div>

    <!-- Right Column: Categories List -->
    <div class="lg:col-span-2 bg-slate-950/80 border border-slate-800/80 rounded-2xl overflow-hidden shadow-xl">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <h4 class="text-xs font-bold uppercase text-slate-400">Existing Categories ({{ $categories->count() }})</h4>
        </div>

        <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-900/90 text-slate-400 uppercase text-[10px] font-bold tracking-wider border-b border-slate-800">
                <tr>
                    <th class="px-6 py-3">Name</th>
                    <th class="px-6 py-3">Slug</th>
                    <th class="px-6 py-3 text-center">Articles</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($categories as $cat)
                    <tr class="hover:bg-slate-900/40 transition">
                        <td class="px-6 py-3.5 font-bold text-white">
                            {{ $cat->name }}
                        </td>
                        <td class="px-6 py-3.5 font-mono text-slate-400">
                            /category/{{ $cat->slug }}
                        </td>
                        <td class="px-6 py-3.5 text-center font-bold text-emerald-400">
                            {{ $cat->posts_count }}
                        </td>
                        <td class="px-6 py-3.5 text-right">
                            <form action="{{ route('admin.categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Delete this category?');" class="inline">
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
                            No categories created for this site yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
