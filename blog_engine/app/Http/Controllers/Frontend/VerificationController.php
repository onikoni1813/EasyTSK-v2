<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\RootFile;
use App\Services\SiteContext;
use Illuminate\Http\Response;

class VerificationController extends Controller
{
    /**
     * Dynamic ads.txt handler per subdomain.
     */
    public function adsTxt(SiteContext $siteContext): Response
    {
        $site = $siteContext->get();
        $content = $site?->ads_txt ?: "# ads.txt for " . ($site?->name ?? 'Blog') . "\n# Add your Adsterra / Monetag / Google AdSense lines in Admin Panel.";

        return response($content, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * Dynamic Root Verification File handler (e.g. sw.js, monetag.html, google12345.html).
     */
    public function rootFile(string $filename, SiteContext $siteContext): Response
    {
        $site = $siteContext->get();
        if (!$site) {
            abort(404);
        }

        $rootFile = RootFile::where('site_id', $site->id)
            ->where('filename', $filename)
            ->first();

        if (!$rootFile) {
            // Check if physical file exists in public directory
            $publicPath = public_path($filename);
            if (file_exists($publicPath) && is_file($publicPath)) {
                return response(file_get_contents($publicPath), 200)
                    ->header('Content-Type', mime_content_type($publicPath) ?: 'text/plain');
            }
            abort(404);
        }

        return response($rootFile->content, 200)
            ->header('Content-Type', $rootFile->mime_type ?: 'text/plain');
    }
}
