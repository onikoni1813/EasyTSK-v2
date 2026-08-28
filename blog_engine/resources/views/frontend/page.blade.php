@extends('layouts.frontend')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 sm:space-y-8">
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-slate-900 transition">Home</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">{{ $page->title }}</span>
    </nav>

    <div class="bg-white rounded-2xl sm:rounded-3xl p-5 sm:p-10 lg:p-12 border border-slate-200/80 shadow-sm space-y-4 sm:space-y-6">
        <h1 class="text-2xl sm:text-4xl font-extrabold text-slate-900 leading-tight font-serif-title border-b border-slate-100 pb-3 sm:pb-4">
            {{ $page->title }}
        </h1>

        <div class="article-content text-slate-700 leading-relaxed font-sans">
            {!! $page->content !!}
        </div>
    </div>
</div>
@endsection
