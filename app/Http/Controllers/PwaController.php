<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;

class PwaController extends Controller
{
    /**
     * Returns the PWA Web App Manifest dynamically generated from AppSettings.
     */
    public function manifest(): JsonResponse
    {
        $siteName = AppSetting::getByKey('site_name', 'Easytsk V2');
        $siteShortName = AppSetting::getByKey('site_short_name', 'Easytsk');
        $siteDescription = AppSetting::getByKey('site_description', 'Earn Money Online with EasyTSK & Offers');
        $siteLogo = AppSetting::getByKey('site_logo', null);
        $siteFavicon = AppSetting::getByKey('site_favicon', '/favicon.ico');

        $iconUrl = $siteLogo ?: $siteFavicon;

        return response()->json([
            'id' => '/',
            'name' => $siteName,
            'short_name' => $siteShortName,
            'description' => $siteDescription,
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'display_override' => ['standalone', 'minimal-ui'],
            'background_color' => '#02040a',
            'theme_color' => '#02040a',
            'orientation' => 'portrait',
            'icons' => [
                [
                    'src' => $iconUrl,
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => $iconUrl,
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ]
            ]
        ], 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'no-cache, private'
        ]);
    }
}
