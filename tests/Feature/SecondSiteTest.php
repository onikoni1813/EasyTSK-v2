<?php

namespace Tests\Feature;

use App\Models\Site;
use Database\Seeders\FirstSiteSeeder;
use Database\Seeders\SecondSiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecondSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FirstSiteSeeder::class);
        $this->seed(SecondSiteSeeder::class);
    }

    public function test_second_site_homepage_renders_with_seeded_articles(): void
    {
        $site = Site::where('subdomain', 'guides')->firstOrFail();
        app()->instance('current_site', $site);

        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_second_site_articles_index_search_and_category_filters(): void
    {
        $site = Site::where('subdomain', 'guides')->firstOrFail();
        app()->instance('current_site', $site);

        // All Articles
        $response = $this->get('/articles');
        $response->assertStatus(200);

        // Search Filter
        $response = $this->get('/articles?search=Docker');
        $response->assertStatus(200);

        // Category Filter
        $response = $this->get('/articles?category=web-development');
        $response->assertStatus(200);
    }

    public function test_second_site_single_article_with_related_posts(): void
    {
        $site = Site::where('subdomain', 'guides')->firstOrFail();
        app()->instance('current_site', $site);

        $response = $this->get('/p/mastering-vue-3-composition-api');
        $response->assertStatus(200);
    }

    public function test_multi_tenant_isolation_between_first_and_second_site(): void
    {
        $siteTools = Site::where('subdomain', 'tools')->firstOrFail();
        $siteGuides = Site::where('subdomain', 'guides')->firstOrFail();

        // 1. Try accessing Site 2 guide on Site 1 -> MUST 404
        app()->instance('current_site', $siteTools);
        $response = $this->get('/p/mastering-vue-3-composition-api');
        $response->assertStatus(404);

        // 2. Try accessing Site 1 guide on Site 2 -> MUST 404
        app()->instance('current_site', $siteGuides);
        $response = $this->get('/p/how-to-use-word-counter');
        $response->assertStatus(404);
    }
}
