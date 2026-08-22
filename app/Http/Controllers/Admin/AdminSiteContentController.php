<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SitePage;
use App\Models\SitePost;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminSiteContentController extends Controller
{
    public function index(Site $site)
    {
        $site->load(['pages', 'posts.category', 'categories']);

        return Inertia::render('Admin/Sites/Content/Index', [
            'site' => $site,
            'pages' => $site->pages()->latest()->get(),
            'posts' => $site->posts()->with('category')->latest()->get(),
            'categories' => $site->categories()->orderBy('sort_order')->get(),
        ]);
    }

    // Pages CRUD
    public function storePage(Request $request, Site $site)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'is_published' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['title']);
        $validated['site_id'] = $site->id;
        $validated['published_at'] = ($validated['is_published'] ?? true) ? now() : null;

        $site->pages()->create($validated);

        return back()->with('success', 'Page created successfully.');
    }

    public function updatePage(Request $request, Site $site, SitePage $page)
    {
        abort_unless($page->site_id === $site->id, 404);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'is_published' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['title']);
        if (($validated['is_published'] ?? true) && !$page->published_at) {
            $validated['published_at'] = now();
        }

        $page->update($validated);

        return back()->with('success', 'Page updated successfully.');
    }

    public function destroyPage(Site $site, SitePage $page)
    {
        abort_unless($page->site_id === $site->id, 404);
        $page->delete();

        return back()->with('success', 'Page deleted successfully.');
    }

    // Posts CRUD
    public function storePost(Request $request, Site $site)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:site_categories,id',
            'summary' => 'nullable|string',
            'content' => 'nullable|string',
            'featured_image' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'reading_time_minutes' => 'nullable|integer|min:1',
            'is_published' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['title']);
        $validated['site_id'] = $site->id;
        $validated['reading_time_minutes'] = $validated['reading_time_minutes'] ?? 3;
        $validated['published_at'] = ($validated['is_published'] ?? true) ? now() : null;

        $site->posts()->create($validated);

        return back()->with('success', 'Post created successfully.');
    }

    public function updatePost(Request $request, Site $site, SitePost $post)
    {
        abort_unless($post->site_id === $site->id, 404);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:site_categories,id',
            'summary' => 'nullable|string',
            'content' => 'nullable|string',
            'featured_image' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'reading_time_minutes' => 'nullable|integer|min:1',
            'is_published' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['title']);
        if (($validated['is_published'] ?? true) && !$post->published_at) {
            $validated['published_at'] = now();
        }

        $post->update($validated);

        return back()->with('success', 'Post updated successfully.');
    }

    public function destroyPost(Site $site, SitePost $post)
    {
        abort_unless($post->site_id === $site->id, 404);
        $post->delete();

        return back()->with('success', 'Post deleted successfully.');
    }

    // Categories CRUD
    public function storeCategory(Request $request, Site $site)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);
        $validated['site_id'] = $site->id;

        $site->categories()->create($validated);

        return back()->with('success', 'Category created successfully.');
    }

    public function updateCategory(Request $request, Site $site, SiteCategory $category)
    {
        abort_unless($category->site_id === $site->id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);

        $category->update($validated);

        return back()->with('success', 'Category updated successfully.');
    }

    public function destroyCategory(Site $site, SiteCategory $category)
    {
        abort_unless($category->site_id === $site->id, 404);
        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }
}
