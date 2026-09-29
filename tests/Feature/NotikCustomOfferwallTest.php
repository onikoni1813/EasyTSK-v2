<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Offerwall;
use App\Models\OfferwallLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotikCustomOfferwallTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Offerwall $notik;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'user',
            'main_balance' => 100,
            'pending_balance' => 0,
        ]);

        $this->notik = Offerwall::create([
            'name' => 'Notik',
            'iframe_url_pattern' => 'https://notik.me/coins?api_key=test_api_key&pub_id=pub_123&app_id=app_456&user_id={user_id}',
            'api_key' => 'test_api_key',
            'pub_id' => 'pub_123',
            'app_id' => 'app_456',
            'secret_key' => 'notik_secret_789',
            'reward_ratio' => 1.00,
            'is_api' => true,
            'status' => true,
            'param_user_id' => 'user_id',
            'param_amount' => 'payout',
            'param_transaction_id' => 'txn_id',
            'param_status' => 'status',
            'param_secret_key' => 'hash',
            'status_chargeback_value' => '2',
        ]);

        AppSetting::setByKey('conversion_rate', 100);
        AppSetting::setByKey('offerwall_pending_hours', 24);
    }

    public function test_guest_cannot_access_notik_offers_endpoint(): void
    {
        $response = $this->getJson('/offerwall/notik/offers');
        $response->assertStatus(401);
    }

    public function test_unconfigured_notik_returns_warning_message(): void
    {
        $this->notik->update([
            'api_key' => null,
            'pub_id' => null,
            'app_id' => null,
            'iframe_url_pattern' => '',
        ]);

        $response = $this->actingAs($this->user)->getJson('/offerwall/notik/offers');
        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'is_configured' => false,
        ]);
    }

    public function test_user_can_fetch_filtered_offers_successfully(): void
    {
        Http::fake([
            'https://notik.me/api/v1/get-offers/filtered*' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'offer_id' => 'notik_101',
                        'name' => 'Hero Wars Action RPG',
                        'image_url' => 'https://notik.me/assets/img/herowars.png',
                        'payout' => 2.50,
                        'click_url' => 'https://notik.me/click?id=101&user_id={user_id}',
                        'categories' => ['Games', 'RPG'],
                        'description1' => 'Download and complete Level 10.',
                        'description2' => 'New players only.',
                        'device_os' => 'android',
                    ],
                    [
                        'offer_id' => 'notik_102',
                        'name' => 'Global Opinion Survey',
                        'image_url' => 'https://notik.me/assets/img/survey.png',
                        'payout' => 0.80,
                        'click_url' => 'https://notik.me/click?id=102',
                        'categories' => ['Surveys'],
                        'description1' => 'Answer all questions truthfully.',
                        'device_os' => 'all',
                    ],
                ]
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->getJson('/offerwall/notik/offers');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_configured' => true,
            'total' => 2,
        ]);

        $offers = $response->json('offers');
        $this->assertCount(2, $offers);

        // Check first offer fields & point calculation ($2.50 * 1.0 * 100 = 250 coins)
        $this->assertEquals('notik_101', $offers[0]['id']);
        $this->assertEquals('Hero Wars Action RPG', $offers[0]['name']);
        $this->assertEquals(2.50, $offers[0]['payout_usd']);
        $this->assertEquals(250.0, $offers[0]['reward_coins']);
        $this->assertStringContainsString((string) $this->user->id, $offers[0]['click_url']);
        $this->assertContains('Games', $offers[0]['categories']);

        // Check categories list
        $categories = $response->json('categories');
        $this->assertContains('Games', $categories);
        $this->assertContains('Surveys', $categories);
    }

    public function test_fallback_to_all_offers_if_filtered_is_empty(): void
    {
        Http::fake([
            'https://notik.me/api/v1/get-offers/filtered*' => Http::response(['data' => []], 200),
            'https://notik.me/api/v2/get-offers/all*' => Http::response([
                'data' => [
                    [
                        'offer_id' => 'notik_201',
                        'name' => 'TikTok Install',
                        'payout' => 1.20,
                        'click_url' => 'https://notik.me/click?id=201',
                        'categories' => ['Social Apps'],
                        'description1' => 'Install and open the app.',
                    ]
                ]
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->getJson('/offerwall/notik/offers?refresh=1');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'total' => 1,
        ]);
        $this->assertEquals('notik_201', $response->json('offers.0.id'));
    }

    public function test_notik_postback_with_valid_sha256_hash_credits_pending_balance(): void
    {
        $transId = 'NTK_TX_12345';
        $reward = '2.50';
        $secretKey = $this->notik->secret_key;

        // Formula: sha256(subId . transId . reward . secretKey)
        $hash = hash('sha256', $this->user->id . $transId . $reward . $secretKey);

        $response = $this->post('/postback/notik', [
            'user_id' => $this->user->id,
            'txn_id'  => $transId,
            'payout'  => $reward,
            'status'  => '1',
            'hash'    => $hash,
        ]);

        $response->assertStatus(200);

        // Verification: $2.50 * 1.0 * 100 = 250 coins into pending_balance
        $this->user->refresh();
        $this->assertEquals(250.00, (float) $this->user->pending_balance);

        $this->assertDatabaseHas('offerwall_logs', [
            'transaction_id' => $transId,
            'user_id'        => $this->user->id,
            'provider'       => 'Notik',
            'status'         => 'pending',
            'amount'         => 250.00,
        ]);
    }

    public function test_notik_postback_with_valid_md5_hash_credits_balance(): void
    {
        $transId = 'NTK_MD5_67890';
        $reward = '1.00';
        $secretKey = $this->notik->secret_key;

        // Formula: md5(subId . transId . reward . secretKey)
        $hash = md5($this->user->id . $transId . $reward . $secretKey);

        $response = $this->post('/postback/notik', [
            'user_id' => $this->user->id,
            'txn_id'  => $transId,
            'payout'  => $reward,
            'status'  => '1',
            'hash'    => $hash,
        ]);

        $response->assertStatus(200);

        $this->user->refresh();
        $this->assertEquals(100.00, (float) $this->user->pending_balance);
    }

    public function test_notik_postback_with_invalid_hash_returns_403(): void
    {
        $response = $this->post('/postback/notik', [
            'user_id' => $this->user->id,
            'txn_id'  => 'NTK_FAKE_999',
            'payout'  => '5.00',
            'status'  => '1',
            'hash'    => 'INVALID_FAKE_HASH_XYZ',
        ]);

        $response->assertStatus(403);
    }

    public function test_notik_chargeback_preserves_zero_floor(): void
    {
        // First credit an offer of 200 points
        $transId = 'NTK_CHARGEBACK_TEST';
        $reward = '2.00';
        $secretKey = $this->notik->secret_key;
        $hash = hash('sha256', $this->user->id . $transId . $reward . $secretKey);

        $this->post('/postback/notik', [
            'user_id' => $this->user->id,
            'txn_id'  => $transId,
            'payout'  => $reward,
            'status'  => '1',
            'hash'    => $hash,
        ]);

        $this->user->refresh();
        $this->assertEquals(200.00, (float) $this->user->pending_balance);

        // Now chargeback (status = 2)
        $chargebackHash = hash('sha256', $this->user->id . $transId . $reward . $secretKey);
        $response = $this->post('/postback/notik', [
            'user_id' => $this->user->id,
            'txn_id'  => $transId,
            'payout'  => $reward,
            'status'  => '2',
            'hash'    => $chargebackHash,
        ]);

        $response->assertStatus(200);

        $this->user->refresh();
        $this->assertEquals(0.00, (float) $this->user->pending_balance);
        $this->assertGreaterThanOrEqual(0, $this->user->pending_balance);
        $this->assertGreaterThanOrEqual(0, $this->user->main_balance);

        $this->assertDatabaseHas('offerwall_logs', [
            'transaction_id' => $transId,
            'status'         => 'reversed',
        ]);
    }
}
