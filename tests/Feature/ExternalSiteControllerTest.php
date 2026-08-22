<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\SiteType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalSiteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_site_homepage_renders_index_for_active_site(): void
    {
        $siteType = SiteType::create(['name' => 'Tools', 'slug' => 'tools']);
        $site = Site::create([
            'site_type_id' => $siteType->id,
            'name' => 'Test Tools Property',
            'slug' => 'test-tools',
            'subdomain' => 'tools',
            'primary_domain' => 'tools.easytsk.com',
            'status' => 'active',
            'theme' => 'default',
        ]);

        app()->instance('current_site', $site);

        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_external_site_returns_503_for_maintenance_site(): void
    {
        $siteType = SiteType::create(['name' => 'Tools', 'slug' => 'tools']);
        $site = Site::create([
            'site_type_id' => $siteType->id,
            'name' => 'Maintenance Tools Property',
            'slug' => 'maint-tools',
            'subdomain' => 'maint-tools',
            'primary_domain' => 'maint.easytsk.com',
            'status' => 'maintenance',
            'theme' => 'default',
        ]);

        app()->instance('current_site', $site);

        $response = $this->get('/');

        $response->assertStatus(503);
    }

    public function test_external_site_returns_404_for_disabled_site(): void
    {
        $siteType = SiteType::create(['name' => 'Tools', 'slug' => 'tools']);
        $site = Site::create([
            'site_type_id' => $siteType->id,
            'name' => 'Disabled Tools Property',
            'slug' => 'disabled-tools',
            'subdomain' => 'disabled-tools',
            'primary_domain' => 'disabled.easytsk.com',
            'status' => 'inactive',
            'theme' => 'default',
        ]);

        app()->instance('current_site', $site);

        $response = $this->get('/');

        $response->assertStatus(404);
    }

    public function test_external_site_renders_legal_page(): void
    {
        $siteType = SiteType::create(['name' => 'Tools', 'slug' => 'tools']);
        $site = Site::create([
            'site_type_id' => $siteType->id,
            'name' => 'Test Tools Property',
            'slug' => 'test-tools',
            'subdomain' => 'tools',
            'primary_domain' => 'tools.easytsk.com',
            'status' => 'active',
            'theme' => 'default',
        ]);

        app()->instance('current_site', $site);

        $response = $this->get('/about');

        $response->assertStatus(200);
    }

    public function test_external_site_returns_404_for_unknown_page(): void
    {
        $siteType = SiteType::create(['name' => 'Tools', 'slug' => 'tools']);
        $site = Site::create([
            'site_type_id' => $siteType->id,
            'name' => 'Test Tools Property',
            'slug' => 'test-tools',
            'subdomain' => 'tools',
            'primary_domain' => 'tools.easytsk.com',
            'status' => 'active',
            'theme' => 'default',
        ]);

        app()->instance('current_site', $site);

        $response = $this->get('/p/unknown-page-slug');

        $response->assertStatus(404);
    }
}
