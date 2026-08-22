<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\SiteAdPlacement;
use App\Models\SiteType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdEngineIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_manage_ad_placements(): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
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

        $response = $this->actingAs($admin)->get("/{$adminPath}/sites/{$site->id}/ad-placements");
        $response->assertStatus(200);

        // Store Ad Placement
        $response = $this->actingAs($admin)->post("/{$adminPath}/sites/{$site->id}/ad-placements", [
            'network' => 'adsterra',
            'placement_slot' => 'header_top',
            'ad_code' => '<script src="https://adsterra.com/ad.js"></script>',
            'device_target' => 'all',
            'is_active' => true,
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('site_ad_placements', [
            'site_id' => $site->id,
            'network' => 'adsterra',
            'placement_slot' => 'header_top',
        ]);
    }

    public function test_external_site_shares_active_ad_placements(): void
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

        SiteAdPlacement::create([
            'site_id' => $site->id,
            'network' => 'monetag',
            'placement_slot' => 'header_top',
            'ad_code' => '<script src="https://monetag.com/banner.js"></script>',
            'device_target' => 'all',
            'is_active' => true,
        ]);

        app()->instance('current_site', $site);

        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_disabled_ad_placements_are_hidden(): void
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

        SiteAdPlacement::create([
            'site_id' => $site->id,
            'network' => 'admaven',
            'placement_slot' => 'sidebar',
            'ad_code' => '<script src="https://admaven.com/sidebar.js"></script>',
            'device_target' => 'all',
            'is_active' => false,
        ]);

        app()->instance('current_site', $site);

        $response = $this->get('/');
        $response->assertStatus(200);
    }
}
