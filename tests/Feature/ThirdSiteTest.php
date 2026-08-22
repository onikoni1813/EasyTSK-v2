<?php

namespace Tests\Feature;

use App\Models\Site;
use Database\Seeders\FirstSiteSeeder;
use Database\Seeders\SecondSiteSeeder;
use Database\Seeders\ThirdSiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThirdSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FirstSiteSeeder::class);
        $this->seed(SecondSiteSeeder::class);
        $this->seed(ThirdSiteSeeder::class);
    }

    public function test_third_site_homepage_renders_deals(): void
    {
        $site = Site::where('subdomain', 'promos')->firstOrFail();
        app()->instance('current_site', $site);

        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_third_site_deals_search_and_category_filters(): void
    {
        $site = Site::where('subdomain', 'promos')->firstOrFail();
        app()->instance('current_site', $site);

        // Search Filter
        $response = $this->get('/?search=HOSTING50');
        $response->assertStatus(200);

        // Category Filter
        $response = $this->get('/?category=hosting-cloud');
        $response->assertStatus(200);
    }

    public function test_complete_ecosystem_multi_tenant_isolation(): void
    {
        $siteTools = Site::where('subdomain', 'tools')->firstOrFail();
        $siteGuides = Site::where('subdomain', 'guides')->firstOrFail();
        $sitePromos = Site::where('subdomain', 'promos')->firstOrFail();

        // 1. Site 3 deal not accessible on Site 1
        app()->instance('current_site', $siteTools);
        $response = $this->get('/p/cloud-hosting-50-off');
        $response->assertStatus(404);

        // 2. Site 3 deal not accessible on Site 2
        app()->instance('current_site', $siteGuides);
        $response = $this->get('/p/cloud-hosting-50-off');
        $response->assertStatus(404);

        // 3. Site 3 deal accessible on Site 3
        app()->instance('current_site', $sitePromos);
        $response = $this->get('/p/cloud-hosting-50-off');
        $response->assertStatus(200);
    }
}
