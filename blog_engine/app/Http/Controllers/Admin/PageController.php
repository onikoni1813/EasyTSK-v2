<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SitePage;
use App\Services\SiteContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index(SiteContext $siteContext)
    {
        $site = $siteContext->get();
        $pages = SitePage::orderBy('title')->get();

        return view('admin.pages.index', compact('pages', 'site'));
    }

    public function create(SiteContext $siteContext)
    {
        $site = $siteContext->get();
        return view('admin.pages.create', compact('site'));
    }

    public function store(Request $request, SiteContext $siteContext)
    {
        $site = $siteContext->get();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content' => 'required|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_published' => 'boolean',
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        $validated['slug'] = $slug;
        $validated['site_id'] = $site->id;
        $validated['is_published'] = $request->boolean('is_published', true);

        SitePage::create($validated);

        return redirect()->route('admin.pages.index')->with('success', "Page '{$validated['title']}' created.");
    }

    public function edit(SitePage $page, SiteContext $siteContext)
    {
        $site = $siteContext->get();
        return view('admin.pages.edit', compact('page', 'site'));
    }

    public function update(Request $request, SitePage $page)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'content' => 'required|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_published' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['slug']);
        $validated['is_published'] = $request->boolean('is_published', true);

        $page->update($validated);

        return redirect()->route('admin.pages.index')->with('success', "Page '{$page->title}' updated.");
    }

    public function destroy(SitePage $page)
    {
        $title = $page->title;
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', "Page '{$title}' deleted.");
    }
}
