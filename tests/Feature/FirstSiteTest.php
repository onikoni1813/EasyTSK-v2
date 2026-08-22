<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Database\Seeders\FirstSiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirstSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FirstSiteSeeder::class);
    }

    public function test_first_site_homepage_renders_with_seeded_tools_and_guides(): void
    {
        $site = Site::where('subdomain', 'tools')->firstOrFail();
        app()->instance('current_site', $site);

        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_first_site_tools_index_and_tool_execution(): void
    {
        $site = Site::where('subdomain', 'tools')->firstOrFail();
        app()->instance('current_site', $site);

        // Tools Index
        $response = $this->get('/tools');
        $response->assertStatus(200);

        // Single Tool Runner
        $response = $this->get('/tools/word-counter');
        $response->assertStatus(200);

        // Another Tool Runner
        $response = $this->get('/tools/json-formatter');
        $response->assertStatus(200);
    }

    public function test_first_site_articles_index_and_guide_reading(): void
    {
        $site = Site::where('subdomain', 'tools')->firstOrFail();
        app()->instance('current_site', $site);

        // Articles Index
        $response = $this->get('/articles');
        $response->assertStatus(200);

        // Single Article / Guide
        $response = $this->get('/p/how-to-use-word-counter');
        $response->assertStatus(200);
    }

    public function test_first_site_faq_page(): void
    {
        $site = Site::where('subdomain', 'tools')->firstOrFail();
        app()->instance('current_site', $site);

        $response = $this->get('/p/faq');
        $response->assertStatus(200);
    }

    public function test_first_site_isolates_internal_routes_with_404(): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $user = User::factory()->create();
        $site = Site::where('subdomain', 'tools')->firstOrFail();
        app()->instance('current_site', $site);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(404);

        $response = $this->actingAs($user)->get("/{$adminPath}");
        $response->assertStatus(404);
    }
}
