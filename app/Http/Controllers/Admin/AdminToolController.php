<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\Tool;
use App\Models\ToolCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminToolController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Tools/Index', [
            'tools' => Tool::with('category')->latest()->get(),
            'categories' => ToolCategory::orderBy('sort_order')->get(),
        ]);
    }

    public function storeTool(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tools,slug',
            'category_id' => 'nullable|exists:tool_categories,id',
            'component_name' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'description' => 'nullable|string',
            'execution_type' => 'required|in:client_side,server_side',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);

        Tool::create($validated);

        return back()->with('success', 'Tool added to master registry.');
    }

    public function updateTool(Request $request, Tool $tool)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tools,slug,' . $tool->id,
            'category_id' => 'nullable|exists:tool_categories,id',
            'component_name' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'description' => 'nullable|string',
            'execution_type' => 'required|in:client_side,server_side',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);

        $tool->update($validated);

        return back()->with('success', 'Tool updated successfully.');
    }

    public function destroyTool(Tool $tool)
    {
        $tool->delete();

        return back()->with('success', 'Tool deleted successfully.');
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tool_categories,slug',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);

        ToolCategory::create($validated);

        return back()->with('success', 'Tool category created.');
    }

    public function updateCategory(Request $request, ToolCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tool_categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);

        $category->update($validated);

        return back()->with('success', 'Tool category updated.');
    }

    public function destroyCategory(ToolCategory $category)
    {
        $category->delete();

        return back()->with('success', 'Tool category deleted.');
    }

    public function siteTools(Site $site)
    {
        $site->load('tools');
        $allTools = Tool::with('category')->where('is_active', true)->get();
        $attachedToolIds = $site->tools->pluck('id')->toArray();

        return Inertia::render('Admin/Sites/Tools/Index', [
            'site' => $site,
            'allTools' => $allTools,
            'attachedToolIds' => $attachedToolIds,
        ]);
    }

    public function toggleSiteTool(Request $request, Site $site, Tool $tool)
    {
        if ($site->tools()->where('tool_id', $tool->id)->exists()) {
            $site->tools()->detach($tool->id);
            $msg = 'Tool detached from site.';
        } else {
            $site->tools()->attach($tool->id, [
                'is_featured' => $request->input('is_featured', false),
            ]);
            $msg = 'Tool attached to site.';
        }

        return back()->with('success', $msg);
    }
}
