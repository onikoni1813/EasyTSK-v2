<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SiteAdPlacement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminSiteAdController extends Controller
{
    public function index(Site $site)
    {
        $site->load('adPlacements');

        $slots = [
            'header_top' => 'Header Top (Banner/Header Slot)',
            'content_top' => 'Content Top (Above Article/Tool)',
            'content_bottom' => 'Content Bottom (Below Article/Tool)',
            'sidebar' => 'Sidebar (Sticky/Side Ad)',
            'footer_bottom' => 'Footer Bottom (Footer Banner)',
        ];

        $networks = [
            'adsterra' => 'Adsterra Network',
            'monetag' => 'Monetag Publisher',
            'admaven' => 'AdMaven Ads',
            'hilltopads' => 'HilltopAds',
            'mybid' => 'MyBid Manager',
            'custom' => 'Custom HTML/JS Snippet',
        ];

        return Inertia::render('Admin/Sites/AdPlacements/Index', [
            'site' => $site,
            'adPlacements' => $site->adPlacements,
            'slots' => $slots,
            'networks' => $networks,
        ]);
    }

    public function store(Request $request, Site $site)
    {
        $validated = $request->validate([
            'network' => 'required|in:adsterra,monetag,admaven,hilltopads,mybid,custom',
            'placement_slot' => 'required|in:header_top,content_top,content_bottom,sidebar,footer_bottom',
            'ad_code' => 'required|string',
            'device_target' => 'required|in:all,desktop_only,mobile_only',
            'is_active' => 'boolean',
        ]);

        $site->adPlacements()->updateOrCreate(
            ['placement_slot' => $validated['placement_slot'], 'network' => $validated['network']],
            [
                'ad_code' => $validated['ad_code'],
                'device_target' => $validated['device_target'],
                'is_active' => $validated['is_active'] ?? true,
            ]
        );

        return back()->with('success', 'Ad placement updated successfully.');
    }

    public function toggleStatus(Site $site, SiteAdPlacement $adPlacement)
    {
        $adPlacement->update(['is_active' => !$adPlacement->is_active]);

        return back()->with('success', 'Ad placement status updated.');
    }

    public function destroy(Site $site, SiteAdPlacement $adPlacement)
    {
        $adPlacement->delete();

        return back()->with('success', 'Ad placement deleted successfully.');
    }
}
