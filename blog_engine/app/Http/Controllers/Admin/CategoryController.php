<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\SiteContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(SiteContext $siteContext)
    {
        $site = $siteContext->get();
        $categories = Category::withCount('posts')->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.categories.index', compact('categories', 'site'));
    }

    public function store(Request $request, SiteContext $siteContext)
    {
        $site = $siteContext->get();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        
        $validated['slug'] = $slug;
        $validated['site_id'] = $site->id;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        Category::create($validated);

        return redirect()->route('admin.categories.index')->with('success', "Category '{$validated['name']}' created.");
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['slug']);
        $category->update($validated);

        return redirect()->route('admin.categories.index')->with('success', "Category '{$category->name}' updated.");
    }

    public function destroy(Category $category)
    {
        $name = $category->name;
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', "Category '{$name}' deleted.");
    }
}
