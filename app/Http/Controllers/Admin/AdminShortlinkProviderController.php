<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShortlinkProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminShortlinkProviderController extends Controller
{
    public function index()
    {
        $providers = ShortlinkProvider::orderBy('id', 'asc')->get();

        return Inertia::render('Admin/ShortlinkProviders/Index', [
            'providers' => $providers,
            'presets' => ShortlinkProvider::PRESETS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'api_url' => 'required|url|max:255',
            'api_key' => 'required|string|max:500',
            'daily_limit' => 'nullable|integer|min:1|max:100',
            'is_active' => 'nullable|boolean',
            'icon' => 'nullable|string|max:10',
        ]);

        $slug = Str::slug($validated['name']);
        $count = ShortlinkProvider::where('slug', 'like', "{$slug}%")->count();
        if ($count > 0) {
            $slug .= '-' . ($count + 1);
        }

        ShortlinkProvider::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'api_url' => rtrim($validated['api_url'], '/'),
            'api_key' => trim($validated['api_key']),
            'daily_limit' => $validated['daily_limit'] ?? 1,
            'is_active' => $request->boolean('is_active', true),
            'icon' => $validated['icon'] ?? '🔗',
        ]);

        return back()->with('success', "✅ Shortlink Provider '{$validated['name']}' saved successfully!");
    }

    public function update(Request $request, ShortlinkProvider $shortlink_provider)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'api_url' => 'required|url|max:255',
            'api_key' => 'required|string|max:500',
            'daily_limit' => 'nullable|integer|min:1|max:100',
            'is_active' => 'nullable|boolean',
            'icon' => 'nullable|string|max:10',
        ]);

        $shortlink_provider->update([
            'name' => $validated['name'],
            'api_url' => rtrim($validated['api_url'], '/'),
            'api_key' => trim($validated['api_key']),
            'daily_limit' => $validated['daily_limit'] ?? 1,
            'is_active' => $request->boolean('is_active', true),
            'icon' => $validated['icon'] ?? '🔗',
        ]);

        return back()->with('success', "✅ Provider '{$shortlink_provider->name}' updated successfully!");
    }

    public function destroy(ShortlinkProvider $shortlink_provider)
    {
        $name = $shortlink_provider->name;
        $shortlink_provider->delete();

        return back()->with('success', "🗑️ Provider '{$name}' deleted.");
    }

    public function toggle(ShortlinkProvider $shortlink_provider)
    {
        $shortlink_provider->update([
            'is_active' => !$shortlink_provider->is_active,
        ]);

        $status = $shortlink_provider->is_active ? 'Activated' : 'Deactivated';
        return back()->with('success', "Provider '{$shortlink_provider->name}' {$status}.");
    }
}
