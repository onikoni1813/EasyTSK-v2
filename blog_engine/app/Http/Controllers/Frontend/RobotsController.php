<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\SiteContext;

class RobotsController extends Controller
{
    public function index(SiteContext $siteContext)
    {
        $site = $siteContext->get();
        $sitemapUrl = url('/sitemap.xml');

        $text = "User-agent: *\n";
        $text .= "Allow: /\n";
        $text .= "Disallow: /admin\n";
        $text .= "Disallow: /admin/*\n\n";
        $text .= "Sitemap: {$sitemapUrl}\n";

        return response($text, 200)->header('Content-Type', 'text/plain');
    }
}
