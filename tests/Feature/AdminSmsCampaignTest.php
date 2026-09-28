<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\SmsCampaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminSmsCampaignTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'phone' => '01800000000',
        ]);

        $this->user = User::factory()->create([
            'role' => 'user',
            'phone' => '01711112222',
            'main_balance' => 1000,
        ]);

        User::factory()->create([
            'role' => 'user',
            'phone' => '01933334444',
            'main_balance' => 100,
        ]);

        AppSetting::setByKey('bulksmsdhaka_api_key', 'test_api_key');
        AppSetting::setByKey('bulksmsdhaka_enabled', 'true');
        AppSetting::setByKey('bulksmsdhaka_sender_id', '1234');
    }

    public function test_non_admin_cannot_access_sms_campaign_page(): void
    {
        $response = $this->actingAs($this->user)->get('/secret-panel/sms-campaign');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_sms_campaign_index_with_props(): void
    {
        Http::fake([
            'https://bulksmsdhaka.net/*' => Http::response(['Status' => '1000', 'Balance' => '229.10', 'Message' => 'Success'], 200),
            'https://bulksmsdhaka.com/*' => Http::response(['Status' => '1000', 'Balance' => '229.10', 'Message' => 'Success'], 200),
        ]);

        $response = $this->actingAs($this->admin)->get('/secret-panel/sms-campaign');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/SmsCampaign/Index')
            ->has('audienceCounts')
            ->where('isEnabled', true)
            ->where('senderId', '1234')
        );
    }

    public function test_admin_can_send_test_sms(): void
    {
        Http::fake([
            'https://bulksmsdhaka.net/*' => Http::response(['Status' => '1000', 'Success' => 'true', 'Message' => 'SMS Sent Successfully'], 200),
            'https://bulksmsdhaka.com/*' => Http::response(['Status' => '1000', 'Success' => 'true', 'Message' => 'SMS Sent Successfully'], 200),
        ]);

        $response = $this->actingAs($this->admin)->post('/secret-panel/sms-campaign/test', [
            'phone' => '01712345678',
            'message' => 'EasyTsk: Test message for admin verification.',
        ]);

        $response->assertSessionHas('success');
    }

    public function test_admin_cannot_launch_campaign_if_gateway_is_disabled(): void
    {
        AppSetting::setByKey('bulksmsdhaka_enabled', 'false');

        $response = $this->actingAs($this->admin)->post('/secret-panel/sms-campaign/send', [
            'filter_type' => 'all',
            'message' => 'EasyTsk: System alert message.',
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('sms_campaigns', 0);
    }

    public function test_admin_can_launch_bulk_campaign_successfully(): void
    {
        Http::fake([
            'https://bulksmsdhaka.net/*' => Http::response(['Status' => '1000', 'Success' => 'true', 'Message' => 'SMS Sent Successfully'], 200),
            'https://bulksmsdhaka.com/*' => Http::response(['Status' => '1000', 'Success' => 'true', 'Message' => 'SMS Sent Successfully'], 200),
        ]);

        $response = $this->actingAs($this->admin)->post('/secret-panel/sms-campaign/send', [
            'filter_type' => 'all',
            'title' => 'Weekly Task Blast',
            'message' => 'EasyTsk: New high paying tasks are available now! Check easytsk.com/tasks',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sms_campaigns', [
            'admin_id' => $this->admin->id,
            'filter_type' => 'all',
            'title' => 'Weekly Task Blast',
            'status' => 'completed',
        ]);

        $campaign = SmsCampaign::first();
        $this->assertNotNull($campaign);
        $this->assertGreaterThan(0, $campaign->recipient_count);
        $this->assertEquals($campaign->recipient_count, $campaign->sent_count);
        $this->assertEquals(0, $campaign->failed_count);
    }

    public function test_admin_can_launch_campaign_to_balance_gt_500_filter(): void
    {
        Http::fake([
            'https://bulksmsdhaka.net/*' => Http::response(['Status' => '1000', 'Success' => 'true', 'Message' => 'SMS Sent Successfully'], 200),
            'https://bulksmsdhaka.com/*' => Http::response(['Status' => '1000', 'Success' => 'true', 'Message' => 'SMS Sent Successfully'], 200),
        ]);

        $response = $this->actingAs($this->admin)->post('/secret-panel/sms-campaign/send', [
            'filter_type' => 'balance_gt_500',
            'title' => 'Withdraw Reminder',
            'message' => 'EasyTsk: You are near payout limit! Withdraw now at easytsk.com/withdraw',
        ]);

        $response->assertSessionHas('success');

        $campaign = SmsCampaign::latest()->first();
        $this->assertEquals('balance >= 500 Pts', $campaign->filter_type);
        // Only admin (if balance >= 500) and $this->user (balance 1000) match
        $this->assertEquals(1, $campaign->recipient_count);
    }

    public function test_admin_can_query_dynamic_audience_count(): void
    {
        // $this->user has balance 1000, other user has balance 100
        $response = $this->actingAs($this->admin)->get('/secret-panel/sms-campaign/count?filter_type=balance_min&min_balance=500');
        $response->assertOk();
        $response->assertJson([
            'count' => 1,
        ]);

        $responseAll = $this->actingAs($this->admin)->get('/secret-panel/sms-campaign/count?filter_type=balance_min&min_balance=50');
        $responseAll->assertOk();
        $responseAll->assertJson([
            'count' => 2,
        ]);
    }

    public function test_admin_can_launch_campaign_with_custom_dynamic_balance(): void
    {
        Http::fake([
            'https://bulksmsdhaka.net/*' => Http::response(['Status' => '1000', 'Success' => 'true', 'Message' => 'SMS Sent Successfully'], 200),
            'https://bulksmsdhaka.com/*' => Http::response(['Status' => '1000', 'Success' => 'true', 'Message' => 'SMS Sent Successfully'], 200),
        ]);

        $response = $this->actingAs($this->admin)->post('/secret-panel/sms-campaign/send', [
            'filter_type' => 'balance_min',
            'min_balance' => 800,
            'title' => 'High Earners Bonus',
            'message' => 'EasyTsk: Special task bonus for top earners! Check easytsk.com',
        ]);

        $response->assertSessionHas('success');

        $campaign = SmsCampaign::latest()->first();
        $this->assertEquals('balance >= 800 Pts', $campaign->filter_type);
        $this->assertEquals(1, $campaign->recipient_count);
    }
}
