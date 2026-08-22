<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class AdminSiteTypeController extends Controller
{
    public function index()
    {
        $siteTypes = SiteType::withCount('sites')->latest()->get();

        return Inertia::render('Admin/SiteTypes/Index', [
            'siteTypes' => $siteTypes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        SiteType::create($validated);

        return back()->with('success', 'Site type created successfully.');
    }

    public function update(Request $request, SiteType $siteType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        $siteType->update($validated);

        return back()->with('success', 'Site type updated successfully.');
    }

    public function destroy(SiteType $siteType)
    {
        if ($siteType->sites()->count() > 0) {
            return back()->withErrors(['message' => 'Cannot delete site type associated with existing sites.']);
        }

        $siteType->delete();

        return back()->with('success', 'Site type deleted successfully.');
    }
}
