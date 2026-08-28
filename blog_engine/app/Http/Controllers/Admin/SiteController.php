<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SitePage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SiteController extends Controller
{
    public function index()
    {
        $sites = Site::withCount(['posts', 'categories', 'adPlacements'])->orderBy('id')->get();
        return view('admin.sites.index', compact('sites'));
    }

    public function create()
    {
        return view('admin.sites.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:be_sites,slug',
            'subdomain' => 'required|string|max:255|unique:be_sites,subdomain',
            'domain' => 'nullable|string|max:255|unique:be_sites,domain',
            'niche' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'theme_color' => 'required|string|max:50',
            'theme_layout' => 'required|string|max:50',
            'is_active' => 'boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        // SEO defaults
        $validated['seo_defaults'] = [
            'meta_title' => $request->input('meta_title', $validated['name']),
            'meta_description' => $request->input('meta_description', $validated['tagline'] ?? $validated['description'] ?? ''),
        ];

        $site = Site::create($validated);

        // Auto-seed default legal pages for publisher readiness (Adsterra/Monetag approval)
        $this->seedLegalPages($site);

        return redirect()->route('admin.sites.index')->with('success', "Site '{$site->name}' created successfully with ready publisher pages!");
    }

    public function edit(Site $site)
    {
        return view('admin.sites.edit', compact('site'));
    }

    public function update(Request $request, Site $site)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:be_sites,slug,' . $site->id,
            'subdomain' => 'required|string|max:255|unique:be_sites,subdomain,' . $site->id,
            'domain' => 'nullable|string|max:255|unique:be_sites,domain,' . $site->id,
            'niche' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'theme_color' => 'required|string|max:50',
            'theme_layout' => 'required|string|max:50',
            'header_scripts' => 'nullable|string',
            'footer_scripts' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        // Update SEO defaults
        $seo = $site->seo_defaults ?? [];
        $seo['meta_title'] = $request->input('meta_title', $seo['meta_title'] ?? $validated['name']);
        $seo['meta_description'] = $request->input('meta_description', $seo['meta_description'] ?? '');
        $validated['seo_defaults'] = $seo;

        $site->update($validated);

        return redirect()->route('admin.sites.index')->with('success', "Site '{$site->name}' updated successfully.");
    }

    public function destroy(Site $site)
    {
        $name = $site->name;
        $site->delete();

        return redirect()->route('admin.sites.index')->with('success', "Site '{$name}' and its isolated data have been removed.");
    }

    protected function seedLegalPages(Site $site): void
    {
        $pages = [
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => "<h2>Privacy Policy for {$site->name}</h2><p>At {$site->name}, accessible from {$site->url}, one of our main priorities is the privacy of our visitors. This Privacy Policy document contains types of information that is collected and recorded by {$site->name} and how we use it.</p><h3>Log Files</h3><p>{$site->name} follows a standard procedure of using log files. These files log visitors when they visit websites.</p><h3>Cookies and Web Beacons</h3><p>Like any other website, {$site->name} uses 'cookies'. These cookies are used to store information including visitors' preferences, and the pages on the website that the visitor accessed or visited.</p><h3>Advertising Partners Privacy Policies</h3><p>Third-party ad servers or ad networks use technologies like cookies, JavaScript, or Web Beacons that are used in their respective advertisements and links that appear on {$site->name}.</p>",
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'content' => "<h2>Terms of Service</h2><p>Welcome to {$site->name}! By accessing this website we assume you accept these terms and conditions. Do not continue to use {$site->name} if you do not agree to take all of the terms and conditions stated on this page.</p><h3>Disclaimer</h3><p>The information provided by {$site->name} is for general informational purposes only. All information on the Site is provided in good faith, however we make no representation or warranty of any kind, express or implied.</p>",
            ],
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => "<h2>About {$site->name}</h2><p>Welcome to {$site->name}, your number one source for the latest updates, tutorials, and expert insights in the {$site->niche} space. We are dedicated to giving you the very best of curated content, with a focus on reliability, accuracy, and depth.</p>",
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact',
                'content' => "<h2>Contact {$site->name}</h2><p>If you have any questions, feedback, or business inquiries, feel free to reach out to our editorial team.</p><p>Email: contact@{$site->subdomain}.easytsk.com</p><p>We typically respond within 24–48 business hours.</p>",
            ],
        ];

        foreach ($pages as $p) {
            SitePage::firstOrCreate(
                ['site_id' => $site->id, 'slug' => $p['slug']],
                [
                    'title' => $p['title'],
                    'content' => $p['content'],
                    'meta_title' => $p['title'] . ' - ' . $site->name,
                    'meta_description' => "Read the {$p['title']} for {$site->name}.",
                    'is_published' => true,
                ]
            );
        }
    }
}
