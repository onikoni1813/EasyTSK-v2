<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSiteRequest;
use App\Http\Requests\Admin\UpdateSiteRequest;
use App\Models\Site;
use App\Models\SiteDomain;
use App\Models\SiteType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminSiteController extends Controller
{
    public function index()
    {
        $sites = Site::with(['siteType', 'domains'])
            ->withCount('domains')
            ->latest()
            ->paginate(15);

        return Inertia::render('Admin/Sites/Index', [
            'sites' => $sites,
        ]);
    }

    public function create()
    {
        $siteTypes = SiteType::where('is_active', true)->get();

        return Inertia::render('Admin/Sites/Create', [
            'siteTypes' => $siteTypes,
        ]);
    }

    public function store(StoreSiteRequest $request)
    {
        DB::transaction(function () use ($request) {
            $site = Site::create($request->validated());

            if ($site->primary_domain) {
                SiteDomain::create([
                    'site_id' => $site->id,
                    'domain_name' => strtolower(trim($site->primary_domain)),
                    'is_primary' => true,
                    'is_verified' => true,
                    'ssl_status' => 'active',
                ]);
            }
        });

        return redirect()->route('admin.sites.index')->with('success', 'External site created successfully.');
    }

    public function edit(Site $site)
    {
        $site->load(['siteType', 'domains', 'settings']);
        $siteTypes = SiteType::where('is_active', true)->get();

        return Inertia::render('Admin/Sites/Edit', [
            'site' => $site,
            'siteTypes' => $siteTypes,
        ]);
    }

    public function update(UpdateSiteRequest $request, Site $site)
    {
        DB::transaction(function () use ($request, $site) {
            $site->update($request->validated());

            if ($site->primary_domain) {
                SiteDomain::updateOrCreate(
                    ['site_id' => $site->id, 'is_primary' => true],
                    [
                        'domain_name' => strtolower(trim($site->primary_domain)),
                        'is_verified' => true,
                        'ssl_status' => 'active',
                    ]
                );
            }
        });

        return back()->with('success', 'Site configuration updated successfully.');
    }

    public function toggleStatus(Site $site)
    {
        $newStatus = match ($site->status) {
            'active' => 'inactive',
            'inactive' => 'active',
            'maintenance' => 'active',
        };

        $site->update(['status' => $newStatus]);

        return back()->with('success', "Site status updated to {$newStatus}.");
    }

    public function destroy(Site $site)
    {
        $site->delete();

        return back()->with('success', 'Site deleted from registry.');
    }

    public function storeDomain(Request $request, Site $site)
    {
        $validated = $request->validate([
            'domain_name' => 'required|string|max:255|unique:site_domains,domain_name',
            'is_primary' => 'boolean',
        ]);

        SiteDomain::create([
            'site_id' => $site->id,
            'domain_name' => strtolower(trim($validated['domain_name'])),
            'is_primary' => $validated['is_primary'] ?? false,
            'is_verified' => true,
            'ssl_status' => 'active',
        ]);

        return back()->with('success', 'Domain added successfully.');
    }

    public function destroyDomain(SiteDomain $domain)
    {
        $domain->delete();

        return back()->with('success', 'Domain removed.');
    }

    public function updateSettings(Request $request, Site $site)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|max:255',
            'settings.*.value' => 'nullable|string',
            'settings.*.type' => 'required|in:string,boolean,integer,json',
        ]);

        foreach ($validated['settings'] as $setting) {
            $site->setSetting($setting['key'], $setting['value'], $setting['type']);
        }

        return back()->with('success', 'Site settings updated.');
    }
}
