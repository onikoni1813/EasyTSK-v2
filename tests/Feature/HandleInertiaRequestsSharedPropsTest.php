<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Site;
use App\Models\SiteType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class HandleInertiaRequestsSharedPropsTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_domain_shares_standard_inertia_props(): void
    {
        $user = User::factory()->create([
            'main_balance' => 500.50,
        ]);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new HandleInertiaRequests();
        $props = $middleware->share($request);

        $this->assertFalse($props['isExternal']);
        $this->assertNull($props['currentSite']);
        $this->assertNotNull($props['auth']['user']);
        $this->assertEquals(500.50, $props['auth']['user']['main_balance']);
        $this->assertNotNull($props['siteSettings']);
    }

    public function test_external_domain_shares_public_site_props_and_strips_user_data(): void
    {
        $user = User::factory()->create([
            'main_balance' => 500.50,
        ]);

        $siteType = SiteType::create(['name' => 'Tools', 'slug' => 'tools']);
        $site = Site::create([
            'site_type_id' => $siteType->id,
            'name' => 'Easy Tools Property',
            'slug' => 'easy-tools',
            'subdomain' => 'tools',
            'primary_domain' => 'tools.easytsk.com',
            'status' => 'active',
            'theme' => 'default',
        ]);
        $site->setSetting('primary_color', '#3B82F6');
        $site->setSetting('logo_url', '/images/tools-logo.png');

        app()->instance('current_site', $site);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new HandleInertiaRequests();
        $props = $middleware->share($request);

        $this->assertTrue($props['isExternal']);
        $this->assertNotNull($props['currentSite']);
        $this->assertEquals('Easy Tools Property', $props['currentSite']['name']);
        $this->assertEquals('#3B82F6', $props['currentSite']['primary_color']);
        $this->assertEquals('/images/tools-logo.png', $props['currentSite']['logo']);
        $this->assertNull($props['auth']['user']);
        $this->assertNull($props['siteSettings']);
    }
}
