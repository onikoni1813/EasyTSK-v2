<?php

namespace Tests\Feature;

use App\Models\AdPlacement;
use App\Models\Category;
use App\Models\Post;
use App\Models\RootFile;
use App\Models\Site;
use App\Models\SitePage;
use App\Models\Tag;
use App\Models\TaskCode;
use App\Models\User;
use App\Services\AdEngine;
use App\Services\SiteContext;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiSiteBlogEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_sites_exist_and_isolated(): void
    {
        $site1 = Site::where('subdomain', 'blog1')->first();
        $site2 = Site::where('subdomain', 'blog2')->first();

        $this->assertNotNull($site1);
        $this->assertNotNull($site2);
        $this->assertNotEquals($site1->id, $site2->id);

        // Verify Site 1 posts are isolated
        $site1Posts = Post::where('site_id', $site1->id)->get();
        $site2Posts = Post::where('site_id', $site2->id)->get();

        $this->assertTrue($site1Posts->isNotEmpty());
        $this->assertTrue($site2Posts->isNotEmpty());

        foreach ($site1Posts as $p) {
            $this->assertEquals($site1->id, $p->site_id);
            $this->assertNotEquals($site2->id, $p->site_id);
        }
    }

    public function test_subdomain_routing_and_homepage_render(): void
    {
        $response = $this->get('/?site=blog1');
        $response->assertStatus(200);
        $response->assertSee('CryptoPulse');

        $response2 = $this->get('/?site=blog2');
        $response2->assertStatus(200);
        $response2->assertSee('TechVibe');
    }

    public function test_single_article_and_ad_isolation(): void
    {
        $site1 = Site::where('subdomain', 'blog1')->first();
        $site2 = Site::where('subdomain', 'blog2')->first();
        $post = Post::where('site_id', $site1->id)->first();

        // Configure ad exclusively for Site 1
        AdPlacement::create([
            'site_id' => $site1->id,
            'placement_slot' => 'in_content_p2',
            'network' => 'adsterra',
            'title' => 'Site 1 Exclusive Ad',
            'ad_code' => '<div>SITE_1_EXCLUSIVE_ADSTERRA_BANNER</div>',
            'is_active' => true,
        ]);

        // View Site 1 post -> Should see Site 1 ad
        $response1 = $this->get('/' . $post->slug . '?site=' . $site1->id);
        $response1->assertStatus(200);
        $response1->assertSee($post->title);
        $response1->assertSee('SITE_1_EXCLUSIVE_ADSTERRA_BANNER');

        // View Site 2 -> Should NEVER see Site 1 ad
        $response2 = $this->get('/?site=' . $site2->id);
        $response2->assertStatus(200);
        $response2->assertDontSee('SITE_1_EXCLUSIVE_ADSTERRA_BANNER');
    }

    public function test_category_and_tag_archives(): void
    {
        $site1 = Site::where('subdomain', 'blog1')->first();
        $category = Category::where('site_id', $site1->id)->first();
        $tag = Tag::where('site_id', $site1->id)->first();

        $catResponse = $this->get('/category/' . $category->slug . '?site=' . $site1->id);
        $catResponse->assertStatus(200);
        $catResponse->assertSee($category->name);

        $tagResponse = $this->get('/tag/' . $tag->slug . '?site=' . $site1->id);
        $tagResponse->assertStatus(200);
        $tagResponse->assertSee('#' . $tag->name);
    }

    public function test_legal_publisher_pages_render(): void
    {
        $site1 = Site::where('subdomain', 'blog1')->first();
        $page = SitePage::where('site_id', $site1->id)->where('slug', 'privacy-policy')->first();

        $this->assertNotNull($page);
        $response = $this->get('/page/privacy-policy?site=' . $site1->id);
        $response->assertStatus(200);
        $response->assertSee('Privacy Policy');
    }

    public function test_sitemap_xml_isolated_per_site(): void
    {
        $site1 = Site::where('subdomain', 'blog1')->first();
        $response = $this->get('/sitemap.xml?site=' . $site1->id);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('<urlset', false);
    }

    public function test_robots_txt_generation(): void
    {
        $response = $this->get('/robots.txt?site=blog1');
        $response->assertStatus(200);
        $response->assertSee('User-agent: *');
        $response->assertSee('Sitemap:');
    }

    public function test_admin_auth_and_dashboard_access(): void
    {
        $admin = User::where('email', 'admin@easytsk.com')->first();
        $this->assertNotNull($admin);

        // Guest is redirected
        $guestResponse = $this->get('/admin');
        $guestResponse->assertRedirect();

        // Authenticated admin can access dashboard
        $authResponse = $this->actingAs($admin)->get('/admin');
        $authResponse->assertStatus(200);
        $authResponse->assertSee('Overview &amp; Performance', false);
        $authResponse->assertSee('All Sites (Blog 01 - 08)');
    }

    public function test_admin_can_create_new_site_with_auto_seeded_legal_pages(): void
    {
        $admin = User::where('email', 'admin@easytsk.com')->first();

        $response = $this->actingAs($admin)->post('/admin/sites', [
            'name' => 'Fintech Weekly',
            'subdomain' => 'blog4',
            'niche' => 'Fintech',
            'tagline' => 'Global Fintech & Banking News',
            'description' => 'Covering neobanks, cross-border payments, and digital wallets.',
            'theme_color' => '#f59e0b',
            'theme_layout' => 'modern',
            'is_active' => 1,
        ]);

        $response->assertRedirect('/admin/sites');

        $site4 = Site::where('subdomain', 'blog4')->first();
        $this->assertNotNull($site4);
        $this->assertEquals('Fintech Weekly', $site4->name);

        // Verify legal pages were automatically seeded
        $privacy = SitePage::withoutGlobalScopes()->where('site_id', $site4->id)->where('slug', 'privacy-policy')->first();
        $this->assertNotNull($privacy);
        $this->assertStringContainsString('Fintech Weekly', $privacy->content);
    }

    public function test_admin_can_save_ad_placements(): void
    {
        $admin = User::where('email', 'admin@easytsk.com')->first();
        $site1 = Site::where('subdomain', 'blog1')->first();

        $response = $this->actingAs($admin)
            ->withSession(['admin_active_site_id' => $site1->id])
            ->post('/admin/ads/save', [
                'ads' => [
                    'header' => [
                        'network' => 'adsterra',
                        'code' => '<script src="//adsterra.com/header.js"></script>',
                        'is_active' => '1',
                    ],
                    'in_content_p2' => [
                        'network' => 'monetag',
                        'code' => '<div class="monetag-ad"></div>',
                        'is_active' => '1',
                    ]
                ]
            ]);

        $response->assertRedirect('/admin/ads');

        $headerAd = AdPlacement::where('site_id', $site1->id)->where('placement_slot', 'header')->first();
        $this->assertNotNull($headerAd);
        $this->assertTrue($headerAd->is_active);
        $this->assertStringContainsString('adsterra.com', $headerAd->ad_code);
    }

    public function test_admin_can_store_and_delete_ad_placement(): void
    {
        $admin = User::where('email', 'admin@easytsk.com')->first();
        $site1 = Site::where('subdomain', 'blog1')->first();

        // 1. Create new Ad Unit
        $createRes = $this->actingAs($admin)
            ->withSession(['admin_active_site_id' => $site1->id])
            ->post('/admin/ads', [
                'title' => 'Floating Bottom Adsterra',
                'placement_slot' => 'floating_bottom',
                'network' => 'adsterra',
                'ad_code' => '<script src="//adsterra.com/float.js"></script>',
                'is_active' => 1,
            ]);

        $createRes->assertRedirect('/admin/ads');

        $ad = AdPlacement::where('site_id', $site1->id)->where('placement_slot', 'floating_bottom')->first();
        $this->assertNotNull($ad);
        $this->assertEquals('Floating Bottom Adsterra', $ad->title);

        // 2. Delete Ad Unit
        $deleteRes = $this->actingAs($admin)
            ->withSession(['admin_active_site_id' => $site1->id])
            ->delete('/admin/ads/' . $ad->id);

        $deleteRes->assertRedirect('/admin/ads');
        $this->assertNull(AdPlacement::find($ad->id));
    }

    public function test_ad_engine_smart_paragraph_injection(): void
    {
        $site = Site::where('subdomain', 'blog1')->first();
        $siteContext = app(SiteContext::class);
        $siteContext->set($site);

        AdPlacement::create([
            'site_id' => $site->id,
            'placement_slot' => 'in_content_p2',
            'network' => 'adsterra',
            'title' => 'In-Content P2',
            'ad_code' => '<div>P2_AD</div>',
            'is_active' => true,
        ]);

        AdPlacement::create([
            'site_id' => $site->id,
            'placement_slot' => 'in_content_p5',
            'network' => 'monetag',
            'title' => 'In-Content P5',
            'ad_code' => '<div>P5_AD</div>',
            'is_active' => true,
        ]);

        $adEngine = app(AdEngine::class);
        $adEngine->clearCache($site->id);

        $html = "<p>First paragraph.</p><p>Second paragraph.</p><p>Third paragraph.</p><p>Fourth paragraph.</p><p>Fifth paragraph.</p><p>Sixth paragraph.</p>";
        $injected = $adEngine->injectInContent($html);

        $this->assertStringContainsString('ad-p2', $injected);
        $this->assertStringContainsString('ad-p5', $injected);
    }

    public function test_ads_txt_and_root_verification_file_serving(): void
    {
        $site = Site::where('subdomain', 'blog1')->first();
        $site->update(['ads_txt' => 'google.com, pub-9999999999999999, DIRECT, f08c47fec0942fa0']);

        // Check ads.txt
        $adsResponse = $this->get('/ads.txt?site=' . $site->id);
        $adsResponse->assertStatus(200);
        $adsResponse->assertSee('pub-9999999999999999');

        // Create root file (e.g. Monetag sw.js)
        RootFile::create([
            'site_id' => $site->id,
            'filename' => 'sw.js',
            'content' => 'importScripts("https://monetag.com/sw.js");',
            'mime_type' => 'text/javascript',
        ]);

        $fileResponse = $this->get('/sw.js?site=' . $site->id);
        $fileResponse->assertStatus(200);
        $fileResponse->assertSee('monetag.com/sw.js');
    }

    public function test_task_reading_timer_and_anti_cheat_code_verification(): void
    {
        $site = Site::where('subdomain', 'blog1')->first();
        $post = Post::where('site_id', $site->id)->first();

        // 1. Start Task Session
        $startRes = $this->postJson('/api/task/start-session', [
            'site_id' => $site->id,
            'post_id' => $post->id,
        ]);

        $startRes->assertStatus(200);
        $startRes->assertJsonStructure(['enabled', 'session_token', 'timer_seconds']);

        $token = $startRes->json('session_token');

        // 2. Anti-cheat test: User tries to claim code immediately (0 seconds elapsed)
        $earlyClaim = $this->postJson('/api/task/claim-code?site=' . $site->id, [
            'session_token' => $token,
            'post_id' => $post->id,
        ]);
        $earlyClaim->assertStatus(422);

        // 3. Simulate 60 seconds elapsed on the server clock
        $task = TaskCode::withoutGlobalScopes()->where('session_token', $token)->first();
        $task->update(['started_at' => now()->subSeconds(65)]);

        // 4. Claim valid secret code
        $validClaim = $this->postJson('/api/task/claim-code?site=' . $site->id, [
            'session_token' => $token,
            'post_id' => $post->id,
        ]);

        $validClaim->assertStatus(200);
        $secretCode = $validClaim->json('code');
        $this->assertStringStartsWith('TSK-', $secretCode);

        // 5. Main Site EasyTSK verifies the code via API
        $verifyRes = $this->postJson('/api/task/verify-code', [
            'code' => $secretCode,
        ]);

        $verifyRes->assertStatus(200);
        $verifyRes->assertJson([
            'success' => true,
            'valid' => true,
        ]);

        // 6. Anti-Replay: Attempting to submit the same code a second time fails (409 Conflict)
        $replayRes = $this->postJson('/api/task/verify-code', [
            'code' => $secretCode,
        ]);

        $replayRes->assertStatus(409);
        $replayRes->assertJson([
            'success' => false,
            'valid' => false,
        ]);
    }
}
