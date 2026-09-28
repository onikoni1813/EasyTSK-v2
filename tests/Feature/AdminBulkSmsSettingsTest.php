<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBulkSmsSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->user = User::factory()->create([
            'role' => 'user',
        ]);
    }

    public function test_admin_can_view_settings_with_bulksms_props(): void
    {
        AppSetting::setByKey('bulksmsdhaka_api_key', 'my_sample_sms_key');
        AppSetting::setByKey('bulksmsdhaka_enabled', 'true');
        AppSetting::setByKey('bulksmsdhaka_sender_id', 'EASYTSK');

        $response = $this->actingAs($this->admin)->get(route('admin.settings.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Settings/Index')
            ->where('bulksmsApiKey', 'my_sample_sms_key')
            ->where('bulksmsEnabled', true)
            ->where('bulksmsSenderId', 'EASYTSK')
        );
    }

    public function test_admin_can_update_bulksms_settings(): void
    {
        $payload = [
            'conversion_rate' => 100,
            'welcome_bonus' => 50,
            'happy_hour' => false,
            'first_withdraw_limit' => 1000,
            'next_withdraw_limit' => 500,
            'referral_bonus' => 500,
            'referral_target' => 1000,
            'offerwall_pending_hours' => 24,
            'min_withdrawal_health' => 40,
            'demo_users' => 1200,
            'demo_tasks' => 45000,
            'demo_payouts' => 280000,
            'support_email' => 'support@easytsk.com',
            'contact_email' => 'contact@easytsk.com',
            'company_address' => 'Dhaka, Bangladesh',
            'wheel_slot_1' => 10,
            'wheel_slot_2' => 25,
            'wheel_slot_3' => 50,
            'wheel_slot_4' => 100,
            'wheel_slot_5' => 200,
            'wheel_jackpot' => 500,

            // BulkSMS settings
            'bulksmsdhaka_enabled' => true,
            'bulksmsdhaka_api_key' => 'live_bulksms_api_key_8899',
            'bulksmsdhaka_sender_id' => 'EASYTSK',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.settings.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('true', AppSetting::getByKey('bulksmsdhaka_enabled'));
        $this->assertEquals('live_bulksms_api_key_8899', AppSetting::getByKey('bulksmsdhaka_api_key'));
        $this->assertEquals('EASYTSK', AppSetting::getByKey('bulksmsdhaka_sender_id'));
    }

    public function test_check_balance_returns_error_if_no_key_configured(): void
    {
        AppSetting::setByKey('bulksmsdhaka_api_key', '');
        config(['services.bulksmsdhaka.api_key' => null]);
        putenv('BULKSMSDHAKA_API_KEY');

        $response = $this->actingAs($this->admin)->post(route('admin.settings.bulksms.balance'), [
            'api_key' => '',
        ]);

        $response->assertSessionHasErrors('bulksms_balance');
    }

    public function test_send_test_sms_requires_phone(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.settings.bulksms.test'), [
            'phone' => '',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_regular_user_cannot_access_settings_endpoints(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.settings.index'));
        $response->assertStatus(403);

        $postResponse = $this->actingAs($this->user)->post(route('admin.settings.bulksms.test'), [
            'phone' => '01700000000',
        ]);
        $postResponse->assertStatus(403);
    }
}
