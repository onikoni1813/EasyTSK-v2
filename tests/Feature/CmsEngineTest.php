<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SitePage;
use App\Models\SitePost;
use App\Models\SiteType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_manage_site_content(): void
    {
        $adminPath = config('app.admin_path', 'secret-panel');
        $admin = User::factory()->create(['role' => 'admin']);
        $siteType = SiteType::create(['name' => 'Tools', 'slug' => 'tools']);
        $site = Site::create([
            'site_type_id' => $siteType->id,
            'name' => 'Tools Hub',
            'slug' => 'tools-hub',
            'subdomain' => 'tools-hub',
            'primary_domain' => 'tools-hub.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get("/{$adminPath}/sites/{$site->id}/content");
        $response->assertStatus(200);

        // Create Category
        $response = $this->actingAs($admin)->post("/{$adminPath}/sites/{$site->id}/categories", [
            'name' => 'Tutorials',
            'slug' => 'tutorials',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('site_categories', ['site_id' => $site->id, 'name' => 'Tutorials']);

        // Create Page
        $response = $this->actingAs($admin)->post("/{$adminPath}/sites/{$site->id}/pages", [
            'title' => 'User Guide',
            'slug' => 'user-guide',
            'content' => 'Guide content here.',
            'is_published' => true,
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('site_pages', ['site_id' => $site->id, 'slug' => 'user-guide']);

        // Create Post
        $response = $this->actingAs($admin)->post("/{$adminPath}/sites/{$site->id}/posts", [
            'title' => 'Top 10 Online Tools',
            'slug' => 'top-10-tools',
            'content' => 'Full article content.',
            'reading_time_minutes' => 5,
            'is_published' => true,
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('site_posts', ['site_id' => $site->id, 'slug' => 'top-10-tools']);
    }

    public function test_external_site_renders_dynamic_cms_page(): void
    {
        $siteType = SiteType::create(['name' => 'Tools', 'slug' => 'tools']);
        $site = Site::create([
            'site_type_id' => $siteType->id,
            'name' => 'Tools Hub',
            'slug' => 'tools-hub',
            'subdomain' => 'tools-hub',
            'primary_domain' => 'tools-hub.com',
            'status' => 'active',
        ]);

        SitePage::create([
            'site_id' => $site->id,
            'title' => 'Custom FAQ',
            'slug' => 'custom-faq',
            'content' => 'Frequently asked questions content.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        app()->instance('current_site', $site);

        $response = $this->get('/p/custom-faq');
        $response->assertStatus(200);
    }

    public function test_external_site_renders_dynamic_article(): void
    {
        $siteType = SiteType::create(['name' => 'Tools', 'slug' => 'tools']);
        $site = Site::create([
            'site_type_id' => $siteType->id,
            'name' => 'Tools Hub',
            'slug' => 'tools-hub',
            'subdomain' => 'tools-hub',
            'primary_domain' => 'tools-hub.com',
            'status' => 'active',
        ]);

        SitePost::create([
            'site_id' => $site->id,
            'title' => 'How to Compress Images',
            'slug' => 'how-to-compress-images',
            'summary' => 'Short summary of image compression.',
            'content' => 'Detailed article content.',
            'reading_time_minutes' => 4,
            'is_published' => true,
            'published_at' => now(),
        ]);

        app()->instance('current_site', $site);

        $response = $this->get('/p/how-to-compress-images');
        $response->assertStatus(200);
    }

    public function test_external_site_hides_draft_pages_and_posts(): void
    {
        $siteType = SiteType::create(['name' => 'Tools', 'slug' => 'tools']);
        $site = Site::create([
            'site_type_id' => $siteType->id,
            'name' => 'Tools Hub',
            'slug' => 'tools-hub',
            'subdomain' => 'tools-hub',
            'primary_domain' => 'tools-hub.com',
            'status' => 'active',
        ]);

        SitePage::create([
            'site_id' => $site->id,
            'title' => 'Draft Secret Page',
            'slug' => 'draft-secret-page',
            'content' => 'Hidden draft content.',
            'is_published' => false,
        ]);

        app()->instance('current_site', $site);

        $response = $this->get('/p/draft-secret-page');
        $response->assertStatus(404);
    }

    public function test_content_is_strictly_isolated_between_sites(): void
    {
        $siteType = SiteType::create(['name' => 'Tools', 'slug' => 'tools']);
        $siteA = Site::create([
            'site_type_id' => $siteType->id,
            'name' => 'Property A',
            'slug' => 'property-a',
            'subdomain' => 'prop-a',
            'primary_domain' => 'propa.com',
            'status' => 'active',
        ]);

        $siteB = Site::create([
            'site_type_id' => $siteType->id,
            'name' => 'Property B',
            'slug' => 'property-b',
            'subdomain' => 'prop-b',
            'primary_domain' => 'propb.com',
            'status' => 'active',
        ]);

        SitePage::create([
            'site_id' => $siteA->id,
            'title' => 'Exclusive Page A',
            'slug' => 'exclusive-page-a',
            'content' => 'Content for Site A only.',
            'is_published' => true,
        ]);

        // Attempting to access Site A's page on Site B
        app()->instance('current_site', $siteB);

        $response = $this->get('/p/exclusive-page-a');
        $response->assertStatus(404);
    }
}
