<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\SiteContext;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(SiteContext $siteContext)
    {
        $site = $siteContext->get();
        if (!$site) {
            return redirect()->route('admin.sites.index')->with('error', 'Please select or create a blog site first.');
        }

        return view('admin.settings.index', compact('site'));
    }

    public function update(Request $request, SiteContext $siteContext)
    {
        $site = $siteContext->get();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'theme_color' => 'required|string|max:50',
            'theme_layout' => 'required|string|max:50',
            'logo' => 'nullable|image|max:2048',
            'favicon' => 'nullable|file|mimes:ico,png,jpg,svg|max:1024',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'google_analytics_id' => 'nullable|string|max:50',
            'task_timer_seconds' => 'required|integer|min:10|max:300',
            'fixed_secret_code' => 'nullable|string|max:255',
            'task_reward_enabled' => 'nullable|boolean',
            'adblock_detection_enabled' => 'nullable|boolean',
            'header_scripts' => 'nullable|string',
            'footer_scripts' => 'nullable|string',
            'facebook_url' => 'nullable|url',
            'twitter_url' => 'nullable|url',
            'telegram_url' => 'nullable|url',
            'youtube_url' => 'nullable|url',
        ]);

        $validated['task_reward_enabled'] = $request->boolean('task_reward_enabled');
        $validated['adblock_detection_enabled'] = $request->boolean('adblock_detection_enabled');

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = 'logo_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("sites/{$site->id}/branding", $filename, 'public');
            $validated['logo'] = '/storage/' . $path;
        }

        if ($request->hasFile('favicon')) {
            $file = $request->file('favicon');
            $filename = 'favicon_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("sites/{$site->id}/branding", $filename, 'public');
            $validated['favicon'] = '/storage/' . $path;
        }

        $seo = [
            'meta_title' => $request->input('meta_title'),
            'meta_description' => $request->input('meta_description'),
            'keywords' => $request->input('keywords'),
            'google_analytics_id' => $request->input('google_analytics_id'),
        ];
        $validated['seo_defaults'] = $seo;

        $social = [
            'facebook' => $request->input('facebook_url'),
            'twitter' => $request->input('twitter_url'),
            'telegram' => $request->input('telegram_url'),
            'youtube' => $request->input('youtube_url'),
        ];
        $validated['social_links'] = $social;

        $site->update($validated);

        return redirect()->route('admin.settings.index')->with('success', "Settings for '{$site->name}' saved successfully.");
    }
}
