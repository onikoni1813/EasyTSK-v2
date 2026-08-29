<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShortlinkProvider;
use App\Services\ShortlinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminShortlinkProviderController extends Controller
{
    public function index()
    {
        if (!Schema::hasTable('shortlink_providers')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                // Table creation will be handled via migrate
            }
        }

        $tableExists = Schema::hasTable('shortlink_providers');
        $providers = $tableExists ? ShortlinkProvider::orderBy('id', 'asc')->get() : collect();

        return Inertia::render('Admin/ShortlinkProviders/Index', [
            'providers' => $providers,
            'presets' => ShortlinkProvider::PRESETS,
            'tableExists' => $tableExists,
        ]);
    }

    public function store(Request $request)
    {
        if (!Schema::hasTable('shortlink_providers')) {
            Artisan::call('migrate', ['--force' => true]);
        }

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

    /**
     * Test live API connection for a provider.
     */
    public function test(ShortlinkProvider $shortlink_provider, ShortlinkService $shortlinkService): JsonResponse
    {
        $result = $shortlinkService->testProvider($shortlink_provider);

        return response()->json($result);
    }

    /**
     * Sync/Seed all 12 default presets from guidelines into database.
     */
    public function syncPresets()
    {
        if (!Schema::hasTable('shortlink_providers')) {
            Artisan::call('migrate', ['--force' => true]);
        }

        $presets = ShortlinkProvider::PRESETS;
        $added = 0;
        $updated = 0;

        foreach ($presets as $key => $preset) {
            if ($key === 'custom') {
                continue;
            }

            $slug = Str::slug($preset['name']);
            $existing = ShortlinkProvider::where('slug', $slug)
                ->orWhere('name', $preset['name'])
                ->first();

            if ($existing) {
                // If API key was empty, populate it
                if (empty($existing->api_key) && !empty($preset['default_key'])) {
                    $existing->update([
                        'api_key' => $preset['default_key'],
                        'api_url' => $preset['api_url'],
                        'icon' => $preset['icon'],
                    ]);
                    $updated++;
                }
            } else {
                ShortlinkProvider::create([
                    'name' => $preset['name'],
                    'slug' => $slug,
                    'api_url' => $preset['api_url'],
                    'api_key' => $preset['default_key'] ?? '',
                    'icon' => $preset['icon'] ?? '🔗',
                    'daily_limit' => 1,
                    'is_active' => true,
                ]);
                $added++;
            }
        }

        return back()->with('success', "✨ Presets synced successfully! Added: {$added}, Updated: {$updated}");
    }
}
