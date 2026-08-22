<?php

namespace Tests\Feature;

use App\Models\PublisherAccount;
use App\Models\Site;
use App\Models\SiteRevenueLog;
use App\Models\SiteType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_publisher_accounts(): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $admin = User::factory()->create(['role' => 'admin']);

        // View Publisher Accounts Page
        $response = $this->actingAs($admin)->get("/{$adminPath}/revenue/publisher-accounts");
        $response->assertStatus(200);

        // Store Publisher Account
        $response = $this->actingAs($admin)->post("/{$adminPath}/revenue/publisher-accounts", [
            'network' => 'adsterra',
            'account_name' => 'Main Adsterra Account',
            'account_id_or_email' => 'publisher@easytsk.com',
            'payout_method' => 'usdt',
            'min_payout_amount' => 100.00,
            'status' => 'active',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('publisher_accounts', [
            'account_name' => 'Main Adsterra Account',
            'network' => 'adsterra',
        ]);
    }

    public function test_admin_can_log_daily_site_revenue_and_compute_cpm(): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $admin = User::factory()->create(['role' => 'admin']);
        $siteType = SiteType::create(['name' => 'Tools', 'slug' => 'tools']);
        $site = Site::create([
            'site_type_id' => $siteType->id,
            'name' => 'Easy Tools',
            'slug' => 'easy-tools',
            'subdomain' => 'tools',
            'primary_domain' => 'tools.easytsk.com',
            'status' => 'active',
        ]);

        $publisher = PublisherAccount::create([
            'network' => 'monetag',
            'account_name' => 'Monetag Pro',
            'account_id_or_email' => 'monetag@easytsk.com',
            'payout_method' => 'usdt',
            'min_payout_amount' => 50.00,
            'status' => 'active',
        ]);

        // Submit Revenue Log: 5000 impressions, $15.00 revenue -> CPM should be (15 / 5000) * 1000 = $3.00
        $response = $this->actingAs($admin)->post("/{$adminPath}/revenue/logs", [
            'site_id' => $site->id,
            'publisher_account_id' => $publisher->id,
            'network' => 'monetag',
            'log_date' => '2026-08-15',
            'impressions' => 5000,
            'clicks' => 45,
            'revenue_usd' => 15.00,
            'payment_status' => 'unpaid',
        ]);
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('site_revenue_logs', [
            'site_id' => $site->id,
            'network' => 'monetag',
            'impressions' => 5000,
            'revenue_usd' => 15.00,
            'cpm_rate' => 3.00,
        ]);
    }

    public function test_ecosystem_revenue_dashboard_aggregation(): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $admin = User::factory()->create(['role' => 'admin']);
        $siteType = SiteType::create(['name' => 'Tools', 'slug' => 'tools']);
        $site = Site::create([
            'site_type_id' => $siteType->id,
            'name' => 'Easy Tools',
            'slug' => 'easy-tools',
            'subdomain' => 'tools',
            'primary_domain' => 'tools.easytsk.com',
            'status' => 'active',
        ]);

        SiteRevenueLog::create([
            'site_id' => $site->id,
            'network' => 'adsterra',
            'log_date' => '2026-08-14',
            'impressions' => 10000,
            'clicks' => 80,
            'revenue_usd' => 25.00,
            'cpm_rate' => 2.50,
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($admin)->get("/{$adminPath}/revenue");
        $response->assertStatus(200);
    }
}
