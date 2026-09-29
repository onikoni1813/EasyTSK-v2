<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\PushCampaign;
use App\Models\PushSubscription;
use App\Models\Transaction;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_fetch_vapid_public_key(): void
    {
        $response = $this->getJson(route('push.public-key'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'public_key',
            'is_enabled',
        ]);
        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('public_key'));
    }

    public function test_user_can_subscribe_to_push_notifications(): void
    {
        $user = User::factory()->create(['main_balance' => 0]);

        $payload = [
            'endpoint'         => 'https://fcm.googleapis.com/fcm/send/fake-test-subscription-endpoint-12345',
            'public_key'       => 'BC4_FAKE_P256DH_KEY_TEST_STRING_ABCD',
            'auth_token'       => 'FAKE_AUTH_TOKEN_TEST_STRING',
            'content_encoding' => 'aes128gcm',
        ];

        $response = $this->actingAs($user)->postJson(route('push.subscribe'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id'    => $user->id,
            'endpoint'   => 'https://fcm.googleapis.com/fcm/send/fake-test-subscription-endpoint-12345',
            'is_active'  => true,
        ]);
    }

    public function test_user_receives_instant_bonus_on_first_push_subscription(): void
    {
        AppSetting::setByKey('push_bonus_enabled', 'true');
        AppSetting::setByKey('push_bonus_amount', '25');

        $user = User::factory()->create([
            'main_balance'            => 100,
            'has_claimed_push_bonus' => false,
        ]);

        $payload = [
            'endpoint'         => 'https://fcm.googleapis.com/fcm/send/fake-bonus-endpoint-1',
            'public_key'       => 'BC4_FAKE_KEY_1',
            'auth_token'       => 'FAKE_TOKEN_1',
            'content_encoding' => 'aes128gcm',
        ];

        $response = $this->actingAs($user)->postJson(route('push.subscribe'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success'       => true,
            'bonus_awarded' => true,
            'bonus_amount'  => 25,
            'new_balance'   => 125,
        ]);

        $user->refresh();
        $this->assertEquals(125, (float) $user->main_balance);
        $this->assertTrue((bool) $user->has_claimed_push_bonus);

        $this->assertDatabaseHas('transactions', [
            'user_id'        => $user->id,
            'type'           => 'credit',
            'amount'         => 25,
            'reference_type' => 'push_bonus',
        ]);
    }

    public function test_user_does_not_receive_push_bonus_twice(): void
    {
        AppSetting::setByKey('push_bonus_enabled', 'true');
        AppSetting::setByKey('push_bonus_amount', '20');

        $user = User::factory()->create([
            'main_balance'            => 50,
            'has_claimed_push_bonus' => true, // Already claimed before
        ]);

        $payload = [
            'endpoint'         => 'https://fcm.googleapis.com/fcm/send/fake-second-device-endpoint',
            'public_key'       => 'BC4_FAKE_KEY_2',
            'auth_token'       => 'FAKE_TOKEN_2',
            'content_encoding' => 'aes128gcm',
        ];

        $response = $this->actingAs($user)->postJson(route('push.subscribe'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success'       => true,
            'bonus_awarded' => false,
        ]);

        $user->refresh();
        $this->assertEquals(50, (float) $user->main_balance);
    }

    public function test_user_can_unsubscribe_from_push(): void
    {
        $user = User::factory()->create();

        $sub = PushSubscription::create([
            'user_id'    => $user->id,
            'endpoint'   => 'https://fcm.googleapis.com/fcm/send/fake-test-unsub-endpoint-999',
            'public_key' => 'FAKE_PUB_KEY',
            'auth_token' => 'FAKE_AUTH_TOKEN',
            'is_active'  => true,
        ]);

        $response = $this->postJson(route('push.unsubscribe'), [
            'endpoint' => $sub->endpoint,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('push_subscriptions', [
            'id'        => $sub->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_view_sms_and_push_campaign_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/secret-panel/sms-campaign');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/SmsCampaign/Index')
            ->has('pushStats')
            ->has('pushCampaigns')
            ->has('vapidPublicKey')
            ->has('pushBonusEnabled')
            ->has('pushBonusAmount')
        );
    }

    public function test_admin_can_update_push_bonus_settings(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post('/secret-panel/sms-campaign/push-settings', [
            'push_bonus_enabled' => true,
            'push_bonus_amount'  => 50,
        ]);

        $response->assertRedirect();
        $this->assertEquals('true', AppSetting::getByKey('push_bonus_enabled'));
        $this->assertEquals('50', AppSetting::getByKey('push_bonus_amount'));
    }
}
