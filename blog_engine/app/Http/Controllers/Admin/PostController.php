<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\SiteContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index(Request $request, SiteContext $siteContext)
    {
        $site = $siteContext->get();
        if (!$site) {
            return redirect()->route('admin.sites.index')->with('error', 'Please select or create a blog site first.');
        }

        $query = Post::with(['author', 'categories', 'tags'])->latest();

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('be_categories.id', $request->category_id);
            });
        }

        $posts = $query->paginate(15)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('admin.posts.index', compact('posts', 'categories', 'site'));
    }

    public function create(SiteContext $siteContext)
    {
        $site = $siteContext->get();
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        $authors = Author::orderBy('name')->get();

        return view('admin.posts.create', compact('categories', 'tags', 'authors', 'site'));
    }

    public function store(Request $request, SiteContext $siteContext)
    {
        $site = $siteContext->get();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|max:3072',
            'author_id' => 'nullable|exists:be_authors,id',
            'status' => 'required|in:draft,published,scheduled',
            'published_at' => 'nullable|date',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'reading_time' => 'nullable|integer|min:1',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'canonical_url' => 'nullable|url',
            'schema_type' => 'nullable|string|max:50',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:be_categories,id',
            'tags_string' => 'nullable|string',
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        
        // Ensure unique slug per site
        $originalSlug = $slug;
        $counter = 1;
        while (Post::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }
        $validated['slug'] = $slug;
        $validated['site_id'] = $site->id;

        // Handle image upload
        if ($request->hasFile('featured_image')) {
            $file = $request->file('featured_image');
            $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("sites/{$site->id}/posts", $filename, 'public');
            $validated['featured_image'] = '/storage/' . $path;
        }

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_trending'] = $request->boolean('is_trending');

        $post = Post::create($validated);

        // Sync categories
        if (!empty($validated['category_ids'])) {
            $post->categories()->sync($validated['category_ids']);
        }

        // Process tags
        if (!empty($validated['tags_string'])) {
            $tagNames = array_filter(array_map('trim', explode(',', $validated['tags_string'])));
            $tagIds = [];
            foreach ($tagNames as $name) {
                $tag = Tag::firstOrCreate(
                    ['site_id' => $site->id, 'slug' => Str::slug($name)],
                    ['name' => $name]
                );
                $tagIds[] = $tag->id;
            }
            $post->tags()->sync($tagIds);
        }

        return redirect()->route('admin.posts.index')->with('success', "Post '{$post->title}' created successfully.");
    }

    public function edit(Post $post, SiteContext $siteContext)
    {
        $site = $siteContext->get();
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        $authors = Author::orderBy('name')->get();

        $selectedCategories = $post->categories->pluck('id')->toArray();
        $tagsString = $post->tags->pluck('name')->implode(', ');

        return view('admin.posts.edit', compact('post', 'categories', 'tags', 'authors', 'selectedCategories', 'tagsString', 'site'));
    }

    public function update(Request $request, Post $post, SiteContext $siteContext)
    {
        $site = $siteContext->get();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|max:3072',
            'author_id' => 'nullable|exists:be_authors,id',
            'status' => 'required|in:draft,published,scheduled',
            'published_at' => 'nullable|date',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'reading_time' => 'nullable|integer|min:1',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'canonical_url' => 'nullable|url',
            'schema_type' => 'nullable|string|max:50',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:be_categories,id',
            'tags_string' => 'nullable|string',
        ]);

        $slug = Str::slug($validated['slug']);
        if ($slug !== $post->slug) {
            $originalSlug = $slug;
            $counter = 1;
            while (Post::where('slug', $slug)->where('id', '!=', $post->id)->exists()) {
                $slug = "{$originalSlug}-{$counter}";
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        // Handle image upload
        if ($request->hasFile('featured_image')) {
            $file = $request->file('featured_image');
            $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("sites/{$site->id}/posts", $filename, 'public');
            $validated['featured_image'] = '/storage/' . $path;
        }

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = $post->published_at ?: now();
        }

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_trending'] = $request->boolean('is_trending');

        $post->update($validated);

        // Sync categories
        $post->categories()->sync($validated['category_ids'] ?? []);

        // Process tags
        if (isset($validated['tags_string'])) {
            $tagNames = array_filter(array_map('trim', explode(',', $validated['tags_string'])));
            $tagIds = [];
            foreach ($tagNames as $name) {
                $tag = Tag::firstOrCreate(
                    ['site_id' => $site->id, 'slug' => Str::slug($name)],
                    ['name' => $name]
                );
                $tagIds[] = $tag->id;
            }
            $post->tags()->sync($tagIds);
        }

        return redirect()->route('admin.posts.index')->with('success', "Post '{$post->title}' updated successfully.");
    }

    public function destroy(Post $post)
    {
        $title = $post->title;
        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', "Post '{$title}' deleted.");
    }
}
