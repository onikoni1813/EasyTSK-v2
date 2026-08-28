@extends('layouts.admin')

@section('title', 'Media Gallery')
@section('page-title', 'Media Gallery for: ' . ($site ? $site->name : 'Blog'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-lg font-bold text-white">Media Asset Library</h3>
            <p class="text-xs text-slate-400">All media uploaded here is safely stored in site-isolated storage directories.</p>
        </div>

        <!-- Upload Form -->
        <form action="{{ route('admin.media.store') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
            @csrf
            <input type="file" name="file" required class="text-xs text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200">
            <button type="submit" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold text-xs rounded-xl shadow-lg shadow-emerald-500/20 transition flex-shrink-0">
                Upload
            </button>
        </form>
    </div>

    <!-- Media Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
        @forelse($media as $item)
            <div class="p-2.5 rounded-2xl bg-slate-950/80 border border-slate-800/80 group space-y-2">
                <div class="aspect-square rounded-xl overflow-hidden bg-slate-900 flex items-center justify-center border border-slate-800/60 relative">
                    @if(str_contains($item->mime_type ?? '', 'image'))
                        <img src="{{ $item->file_path }}" alt="{{ $item->alt_text }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-xs font-mono text-slate-400">{{ $item->mime_type }}</span>
                    @endif

                    <div class="absolute inset-0 bg-slate-950/80 opacity-0 group-hover:opacity-100 flex items-center justify-center gap-2 transition p-2">
                        <button type="button" onclick="navigator.clipboard.writeText('{{ url($item->file_path) }}'); alert('Image URL copied to clipboard!');" class="p-1.5 bg-emerald-500 text-slate-950 rounded-lg text-xs font-bold" title="Copy URL">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                        </button>

                        <form action="{{ route('admin.media.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete this media?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 bg-rose-500 text-white rounded-lg text-xs" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="truncate text-[11px] font-semibold text-slate-300">{{ $item->name }}</div>
                <div class="text-[9px] text-slate-500 font-mono">{{ round($item->file_size / 1024, 1) }} KB</div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-500">
                No media assets uploaded for this site yet.
            </div>
        @endforelse
    </div>

    @if($media->hasPages())
        <div class="pt-4">
            {{ $media->links() }}
        </div>
    @endif
</div>
@endsection
