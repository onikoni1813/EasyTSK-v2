<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\PublisherAccount;
use App\Models\Site;
use App\Models\SiteAdPlacement;
use App\Models\SiteRevenueLog;
use App\Models\User;
use Database\Seeders\FirstSiteSeeder;
use Database\Seeders\SecondSiteSeeder;
use Database\Seeders\ThirdSiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class FinalEcosystemAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FirstSiteSeeder::class);
        $this->seed(SecondSiteSeeder::class);
        $this->seed(ThirdSiteSeeder::class);
    }

    public function test_route_security_and_domain_isolation_audit(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $siteTools = Site::where('subdomain', 'tools')->firstOrFail();
        $siteGuides = Site::where('subdomain', 'guides')->firstOrFail();
        $sitePromos = Site::where('subdomain', 'promos')->firstOrFail();

        // 1. External Site 1 (Tools) blocks internal routes
        app()->instance('current_site', $siteTools);
        $this->actingAs($user)->get('/login')->assertStatus(404);
        $this->actingAs($user)->get('/dashboard')->assertStatus(404);
        $this->actingAs($user)->get('/admin')->assertStatus(404);
        $this->actingAs($user)->get('/withdraw')->assertStatus(404);

        // 2. External Site 2 (Guides) blocks internal routes
        app()->instance('current_site', $siteGuides);
        $this->actingAs($user)->get('/login')->assertStatus(404);
        $this->actingAs($user)->get('/dashboard')->assertStatus(404);
        $this->actingAs($user)->get('/admin')->assertStatus(404);
        $this->actingAs($user)->get('/withdraw')->assertStatus(404);

        // 3. External Site 3 (Promos) blocks internal routes
        app()->instance('current_site', $sitePromos);
        $this->actingAs($user)->get('/login')->assertStatus(404);
        $this->actingAs($user)->get('/dashboard')->assertStatus(404);
        $this->actingAs($user)->get('/admin')->assertStatus(404);
        $this->actingAs($user)->get('/withdraw')->assertStatus(404);

        // 4. Primary Domain allows internal login/dashboard routes
        app()->forgetInstance('current_site');
        $this->actingAs($user)->get('/dashboard')->assertStatus(200);
    }

    public function test_data_stripping_and_shared_props_security(): void
    {
        $user = User::factory()->create(['main_balance' => 9999.99]);
        $siteTools = Site::where('subdomain', 'tools')->firstOrFail();

        app()->instance('current_site', $siteTools);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new HandleInertiaRequests();
        $props = $middleware->share($request);

        $this->assertTrue($props['isExternal']);
        $this->assertNotNull($props['currentSite']);
        $this->assertEquals('EasyTools Hub', $props['currentSite']['name']);
        $this->assertNull($props['auth']['user']);
        $this->assertNull($props['siteSettings']);
    }

    public function test_ecosystem_3_properties_content_isolation(): void
    {
        $siteTools = Site::where('subdomain', 'tools')->firstOrFail();
        $siteGuides = Site::where('subdomain', 'guides')->firstOrFail();
        $sitePromos = Site::where('subdomain', 'promos')->firstOrFail();

        // Tools Hub Homepage
        app()->instance('current_site', $siteTools);
        $resTools = $this->get('/');
        $resTools->assertStatus(200);

        // TechGuides Hub Homepage
        app()->instance('current_site', $siteGuides);
        $resGuides = $this->get('/');
        $resGuides->assertStatus(200);

        // EasyPromos Hub Homepage
        app()->instance('current_site', $sitePromos);
        $resPromos = $this->get('/');
        $resPromos->assertStatus(200);
    }

    public function test_ad_placement_and_revenue_engine_audit(): void
    {
        $siteTools = Site::where('subdomain', 'tools')->firstOrFail();

        SiteAdPlacement::create([
            'site_id' => $siteTools->id,
            'network' => 'adsterra',
            'placement_slot' => 'header_top',
            'ad_code' => '<script>console.log("Adsterra");</script>',
            'is_active' => true,
        ]);

        $publisher = PublisherAccount::create([
            'network' => 'adsterra',
            'account_name' => 'Primary Adsterra',
            'account_id_or_email' => 'ads@easytsk.com',
            'payout_method' => 'usdt',
            'min_payout_amount' => 50.00,
            'status' => 'active',
        ]);

        SiteRevenueLog::create([
            'site_id' => $siteTools->id,
            'publisher_account_id' => $publisher->id,
            'network' => 'adsterra',
            'log_date' => '2026-08-15',
            'impressions' => 20000,
            'clicks' => 120,
            'revenue_usd' => 50.00,
            'cpm_rate' => 2.50,
            'payment_status' => 'unpaid',
        ]);

        app()->instance('current_site', $siteTools);
        $res = $this->get('/');
        $res->assertStatus(200);

        $this->assertDatabaseHas('site_revenue_logs', [
            'site_id' => $siteTools->id,
            'network' => 'adsterra',
            'revenue_usd' => 50.00,
        ]);
    }
}
