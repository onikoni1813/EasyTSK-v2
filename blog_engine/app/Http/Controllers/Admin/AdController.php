<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdPlacement;
use App\Services\AdEngine;
use App\Services\SiteContext;
use Illuminate\Http\Request;

class AdController extends Controller
{
    public function index(SiteContext $siteContext)
    {
        $site = $siteContext->get();
        if (!$site) {
            return redirect()->route('admin.sites.index')->with('error', 'Please select or create a blog site first.');
        }

        $slots = AdPlacement::SLOTS;
        $defaultNetworks = AdPlacement::NETWORKS;

        // Fetch dynamic networks used across all placements
        $customNetworks = AdPlacement::where('site_id', $site->id)
            ->whereNotNull('network')
            ->distinct()
            ->pluck('network')
            ->toArray();

        $networks = $defaultNetworks;
        foreach ($customNetworks as $cn) {
            $cnKey = strtolower(trim($cn));
            if (!isset($networks[$cnKey])) {
                $networks[$cnKey] = ucfirst($cn);
            }
        }

        $existingPlacements = AdPlacement::where('site_id', $site->id)
            ->get()
            ->keyBy('placement_slot');

        $allPlacements = AdPlacement::where('site_id', $site->id)
            ->orderBy('id', 'asc')
            ->get();

        return view('admin.ads.index', compact('site', 'slots', 'networks', 'existingPlacements', 'allPlacements'));
    }

    /**
     * Create a new custom or standard Ad Unit.
     */
    public function store(Request $request, SiteContext $siteContext, AdEngine $adEngine)
    {
        $site = $siteContext->get();
        if (!$site) {
            return redirect()->back()->with('error', 'No active site selected.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'placement_slot' => 'required|string|max:100',
            'network' => 'required|string|max:50',
            'custom_network' => 'nullable|string|max:50',
            'ad_code' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $slotKey = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', trim($validated['placement_slot'])));
        $network = !empty($validated['custom_network']) 
            ? strtolower(trim($validated['custom_network'])) 
            : strtolower(trim($validated['network']));
        
        $isActive = $request->boolean('is_active');

        AdPlacement::updateOrCreate(
            [
                'site_id' => $site->id,
                'placement_slot' => $slotKey,
            ],
            [
                'network' => $network,
                'title' => $validated['title'],
                'ad_code' => $validated['ad_code'] ?? '',
                'is_active' => $isActive,
            ]
        );

        $adEngine->clearCache($site->id);

        return redirect()->route('admin.ads.index')->with('success', "Ad Unit '{$validated['title']}' created successfully under network '{$network}'.");
    }

    /**
     * Bulk save active ad placements.
     */
    public function save(Request $request, SiteContext $siteContext, AdEngine $adEngine)
    {
        $site = $siteContext->get();
        $slots = AdPlacement::SLOTS;
        $adsInput = $request->input('ads', []);

        foreach ($adsInput as $slotKey => $slotData) {
            $adCode = $slotData['code'] ?? null;
            $network = !empty($slotData['custom_network']) ? strtolower(trim($slotData['custom_network'])) : ($slotData['network'] ?? 'adsterra');
            $title = $slotData['title'] ?? ($slots[$slotKey] ?? ucfirst(str_replace('_', ' ', $slotKey)));
            $isActive = !empty($slotData['is_active']);

            if (!empty($adCode) || $isActive) {
                AdPlacement::updateOrCreate(
                    [
                        'site_id' => $site->id,
                        'placement_slot' => $slotKey,
                    ],
                    [
                        'network' => $network,
                        'title' => $title,
                        'ad_code' => $adCode ?? '',
                        'is_active' => $isActive,
                    ]
                );
            } else {
                AdPlacement::where('site_id', $site->id)
                    ->where('placement_slot', $slotKey)
                    ->update(['is_active' => false]);
            }
        }

        $adEngine->clearCache($site->id);

        return redirect()->route('admin.ads.index')->with('success', "Ad configurations for '{$site->name}' updated and cached successfully.");
    }

    /**
     * Delete an Ad Placement permanently by ID or slot key.
     */
    public function destroy(string $idOrSlot, SiteContext $siteContext, AdEngine $adEngine)
    {
        $site = $siteContext->get();
        
        if (is_numeric($idOrSlot)) {
            $placement = AdPlacement::where('site_id', $site->id)->find($idOrSlot);
        } else {
            $placement = AdPlacement::where('site_id', $site->id)->where('placement_slot', $idOrSlot)->first();
        }

        if ($placement) {
            $slotTitle = $placement->title ?: $placement->placement_slot;
            $placement->delete();
            $msg = "Ad Unit '{$slotTitle}' deleted permanently from database.";
        } else {
            $msg = "Ad placement reset successfully.";
        }

        $adEngine->clearCache($site->id);

        return redirect()->route('admin.ads.index')->with('success', $msg);
    }
}
