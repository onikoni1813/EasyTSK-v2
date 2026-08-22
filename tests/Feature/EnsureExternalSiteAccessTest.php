<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureExternalSiteAccess;
use App\Models\Site;
use App\Models\SiteDomain;
use App\Models\SiteType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class EnsureExternalSiteAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_domain_can_access_internal_routes(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_external_domain_blocks_internal_user_routes_with_404(): void
    {
        $siteType = SiteType::create(['name' => 'Tools', 'slug' => 'tools']);
        $site = Site::create([
            'site_type_id' => $siteType->id,
            'name' => 'Test Tools Site',
            'slug' => 'test-tools',
            'subdomain' => 'tools',
            'primary_domain' => 'tools.easytsk.com',
            'status' => 'active',
        ]);

        app()->instance('current_site', $site);

        $request = Request::create('/dashboard', 'GET');
        $middleware = new EnsureExternalSiteAccess();

        $this->expectException(NotFoundHttpException::class);
        $middleware->handle($request, fn() => response('OK'));
    }

    public function test_external_domain_blocks_admin_routes_with_404(): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');

        $siteType = SiteType::create(['name' => 'Tools', 'slug' => 'tools']);
        $site = Site::create([
            'site_type_id' => $siteType->id,
            'name' => 'Test Tools Site',
            'slug' => 'test-tools',
            'subdomain' => 'tools',
            'primary_domain' => 'tools.easytsk.com',
            'status' => 'active',
        ]);

        app()->instance('current_site', $site);

        $request = Request::create("/{$adminPath}", 'GET');
        $middleware = new EnsureExternalSiteAccess();

        $this->expectException(NotFoundHttpException::class);
        $middleware->handle($request, fn() => response('OK'));
    }
}
