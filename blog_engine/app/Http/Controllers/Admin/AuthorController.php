<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Services\SiteContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthorController extends Controller
{
    public function index(SiteContext $siteContext)
    {
        $site = $siteContext->get();
        $authors = Author::withCount('posts')->orderBy('name')->get();

        return view('admin.authors.index', compact('authors', 'site'));
    }

    public function store(Request $request, SiteContext $siteContext)
    {
        $site = $siteContext->get();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'bio' => 'nullable|string',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $validated['slug'] = $slug;
        $validated['site_id'] = $site?->id;

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = 'author_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("authors", $filename, 'public');
            $validated['avatar'] = '/storage/' . $path;
        }

        Author::create($validated);

        return redirect()->route('admin.authors.index')->with('success', "Author '{$validated['name']}' created.");
    }

    public function update(Request $request, Author $author)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'bio' => 'nullable|string',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $validated['slug'] = Str::slug($validated['slug']);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = 'author_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("authors", $filename, 'public');
            $validated['avatar'] = '/storage/' . $path;
        }

        $author->update($validated);

        return redirect()->route('admin.authors.index')->with('success', "Author '{$author->name}' updated.");
    }

    public function destroy(Author $author)
    {
        $name = $author->name;
        $author->delete();

        return redirect()->route('admin.authors.index')->with('success', "Author '{$name}' deleted.");
    }
}
