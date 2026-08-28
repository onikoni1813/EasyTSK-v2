<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Services\SiteContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index(SiteContext $siteContext)
    {
        $site = $siteContext->get();
        $tags = Tag::withCount('posts')->orderBy('name')->get();

        return view('admin.tags.index', compact('tags', 'site'));
    }

    public function store(Request $request, SiteContext $siteContext)
    {
        $site = $siteContext->get();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);

        Tag::create([
            'site_id' => $site->id,
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        return redirect()->route('admin.tags.index')->with('success', "Tag '{$validated['name']}' created.");
    }

    public function destroy(Tag $tag)
    {
        $name = $tag->name;
        $tag->delete();

        return redirect()->route('admin.tags.index')->with('success', "Tag '{$name}' deleted.");
    }
}
