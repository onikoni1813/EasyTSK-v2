<?php

namespace Tests\Feature;

use App\Models\Offerwall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_postback_rejects_unknown_provider(): void
    {
        $response = $this->get('/postback/unknown_provider?user_id=1&transaction_id=TX123&amount=10');
        $response->assertStatus(404);
    }

    public function test_postback_rejects_invalid_secret_key(): void
    {
        Offerwall::create([
            'name' => 'cpx',
            'display_name' => 'CPX Research',
            'iframe_url_pattern' => 'https://cpx.com/wall?subId={user_id}',
            'status' => true,
            'secret_key' => 'my_secret_token_123',
            'param_user_id' => 'user_id',
            'param_transaction_id' => 'transaction_id',
            'param_amount' => 'amount',
            'param_secret_key' => 'secret',
        ]);

        $response = $this->get('/postback/cpx?user_id=1&transaction_id=TX123&amount=10&secret=WRONG_SECRET');
        $response->assertStatus(403);
    }

    public function test_valid_postback_credits_user_balance(): void
    {
        \App\Models\AppSetting::setByKey('offerwall_pending_hours', 0);

        $user = User::factory()->create([
            'main_balance' => 0,
        ]);

        Offerwall::create([
            'name' => 'cpalead',
            'display_name' => 'CPA Lead',
            'iframe_url_pattern' => 'https://cpalead.com/wall?subId={user_id}',
            'status' => true,
            'secret_key' => 'valid_secret_999',
            'param_user_id' => 'user_id',
            'param_transaction_id' => 'transaction_id',
            'param_amount' => 'amount',
            'param_secret_key' => 'secret',
        ]);

        $response = $this->get("/postback/cpalead?user_id={$user->id}&transaction_id=TX999888&amount=50&secret=valid_secret_999");

        $response->assertStatus(200);
        $this->assertEquals(5000, $user->fresh()->main_balance);
        $this->assertDatabaseHas('offerwall_logs', [
            'user_id' => $user->id,
            'transaction_id' => 'TX999888',
            'provider' => 'Cpalead',
        ]);
    }

    public function test_timewall_postback_credits_user_points(): void
    {
        \App\Models\AppSetting::setByKey('offerwall_pending_hours', 0);
        \App\Models\AppSetting::setByKey('conversion_rate', 100);

        $user = User::factory()->create([
            'main_balance' => 0,
        ]);

        Offerwall::create([
            'name' => 'Timewall',
            'display_name' => 'TimeWall',
            'iframe_url_pattern' => 'https://timewall.io/offerwall?user={user_id}',
            'status' => true,
            'secret_key' => 'demosecret123',
            'param_user_id' => 'userID',
            'param_transaction_id' => 'transactionID',
            'param_amount' => 'currencyAmount',
            'param_secret_key' => 'hash',
            'reward_ratio' => 1.0,
        ]);

        // $4.00 USD Payout * 100 conversion_rate = 400 points
        $response = $this->get("/postback/Timewall?userID={$user->id}&transactionID=TW_TEST_1001&currencyAmount=4.00&secret=demosecret123");

        $response->assertStatus(200);
        $this->assertEquals(400, $user->fresh()->main_balance);
    }

    public function test_notik_sha256_postback_credits_user(): void
    {
        \App\Models\AppSetting::setByKey('offerwall_pending_hours', 0);
        \App\Models\AppSetting::setByKey('conversion_rate', 100);

        $user = User::factory()->create([
            'main_balance' => 0,
        ]);

        $secret = 'notik_secret_key_123';
        $payout = '2.50';
        $txnId = 'NOTIK_TX_555';

        Offerwall::create([
            'name' => 'Notik',
            'iframe_url_pattern' => 'https://notik.me/offerwall?pub_id=123&user_id={user_id}',
            'status' => true,
            'secret_key' => $secret,
            'param_user_id' => 'user_id',
            'param_transaction_id' => 'txn_id',
            'param_amount' => 'payout',
            'param_secret_key' => 'hash',
            'reward_ratio' => 1.0,
        ]);

        $sha256Hash = hash('sha256', $user->id . $payout . $secret);

        $response = $this->get("/postback/Notik?user_id={$user->id}&txn_id={$txnId}&payout={$payout}&hash={$sha256Hash}");

        $response->assertStatus(200);
        $this->assertEquals(250, $user->fresh()->main_balance);
    }

    public function test_timewall_sha256_revenue_postback(): void
    {
        \App\Models\AppSetting::setByKey('offerwall_pending_hours', 0);
        \App\Models\AppSetting::setByKey('conversion_rate', 100);

        $user = User::factory()->create([
            'main_balance' => 0,
        ]);

        $secret = '0c0796625344591cc252afc2e52be8d3';
        $revenue = '0.002';
        $txnId = 'TW_SHA_9999';

        Offerwall::create([
            'name' => 'Timewall',
            'iframe_url_pattern' => 'https://timewall.io/offerwall?user={user_id}',
            'status' => true,
            'secret_key' => $secret,
            'param_user_id' => 'userID',
            'param_transaction_id' => 'transactionID',
            'param_amount' => 'currencyAmount',
            'param_secret_key' => 'hash',
            'reward_ratio' => 1.0,
        ]);

        // TimeWall hash formula: hash("sha256", userID . revenue . SecretKey)
        $sha256Hash = hash('sha256', $user->id . $revenue . $secret);

        $response = $this->get("/postback/Timewall?userID={$user->id}&transactionID={$txnId}&revenue={$revenue}&currencyAmount=0.20&hash={$sha256Hash}&type=credit");

        $response->assertStatus(200);
        $this->assertEquals(20, $user->fresh()->main_balance);
    }

    public function test_postback_handles_provider_name_with_spaces(): void
    {
        \App\Models\AppSetting::setByKey('offerwall_pending_hours', 0);
        \App\Models\AppSetting::setByKey('conversion_rate', 100);

        $user = User::factory()->create(['main_balance' => 0]);

        Offerwall::create([
            'name' => 'CPA Lead',
            'iframe_url_pattern' => 'https://cpalead.com/wall?subId={user_id}',
            'status' => true,
            'secret_key' => 'secret_123',
            'param_user_id' => 'user_id',
            'param_transaction_id' => 'transaction_id',
            'param_amount' => 'amount',
            'param_secret_key' => 'secret',
        ]);

        $response = $this->get("/postback/cpalead?user_id={$user->id}&transaction_id=TX_SPACE_1&amount=10&secret=secret_123");
        $response->assertStatus(200);
        $this->assertEquals(1000, $user->fresh()->main_balance);
    }

    public function test_admin_can_toggle_and_delete_offerwall(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $offerwall = Offerwall::create([
            'name' => 'Test Wall',
            'iframe_url_pattern' => 'https://testwall.com?user={user_id}',
            'reward_ratio' => 1.0,
            'status' => true,
        ]);

        // Toggle status
        $toggleResponse = $this->actingAs($admin)->post("/secret-panel/offerwalls/{$offerwall->id}/toggle");
        $toggleResponse->assertRedirect();
        $this->assertFalse((bool) $offerwall->fresh()->status);

        // Delete offerwall
        $deleteResponse = $this->actingAs($admin)->delete("/secret-panel/offerwalls/{$offerwall->id}");
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('offerwalls', ['id' => $offerwall->id]);
    }

    public function test_capsbit_md5_postback_credits_user_and_returns_ok(): void
    {
        \App\Models\AppSetting::setByKey('offerwall_pending_hours', 0);
        \App\Models\AppSetting::setByKey('conversion_rate', 100);

        $user = User::factory()->create(['main_balance' => 0]);
        $secret = 'capsbit_secret_key_123';
        $payout = '0.75';
        $txid = 'CAPS_TX_1001';
        $offerId = '10021';

        Offerwall::create([
            'name' => 'Capsbit',
            'iframe_url_pattern' => 'https://offerwall.capsbit.com/key/{user_id}',
            'status' => true,
            'secret_key' => $secret,
            'param_user_id' => 'uid',
            'param_transaction_id' => 'txid',
            'param_amount' => 'payout',
            'param_secret_key' => 'sig',
            'reward_ratio' => 1.0,
        ]);

        // Formula: md5(uid . payout . offer_id . txid . secret)
        $expectedSig = md5($user->id . $payout . $offerId . $txid . $secret);

        $response = $this->get("/postback/capsbit?uid={$user->id}&txid={$txid}&payout={$payout}&offer_id={$offerId}&status=approved&sig={$expectedSig}");

        $response->assertStatus(200);
        $this->assertEquals('OK', $response->getContent());
        // $0.75 * 100 conversion_rate = 75 coins
        $this->assertEquals(75, $user->fresh()->main_balance);
        $this->assertDatabaseHas('offerwall_logs', [
            'user_id' => $user->id,
            'transaction_id' => $txid,
            'provider' => 'Capsbit',
            'status' => 'approved',
        ]);
    }

    public function test_capsbit_pending_status_acknowledges_without_crediting(): void
    {
        \App\Models\AppSetting::setByKey('offerwall_pending_hours', 0);

        $user = User::factory()->create(['main_balance' => 0]);
        $secret = 'capsbit_sec';
        $txid = 'CAPS_PENDING_1';

        Offerwall::create([
            'name' => 'Capsbit',
            'iframe_url_pattern' => 'https://offerwall.capsbit.com/key/{user_id}',
            'status' => true,
            'secret_key' => $secret,
            'param_user_id' => 'uid',
            'param_transaction_id' => 'txid',
            'param_amount' => 'payout',
            'param_secret_key' => 'sig',
            'reward_ratio' => 1.0,
        ]);

        // status=0 is pending in Capsbit
        $response = $this->get("/postback/capsbit?uid={$user->id}&txid={$txid}&payout=1.00&offer_id=5&status=0&sig=dummy");

        $response->assertStatus(200);
        $this->assertEquals('OK', $response->getContent());
        $this->assertEquals(0, $user->fresh()->main_balance);
        $this->assertDatabaseMissing('offerwall_logs', ['transaction_id' => $txid]);
    }

    public function test_earnwall_postback_credits_user_and_returns_literal_ok(): void
    {
        \App\Models\AppSetting::setByKey('offerwall_pending_hours', 0);
        \App\Models\AppSetting::setByKey('conversion_rate', 100);

        $user = User::factory()->create(['main_balance' => 0]);
        $secret = 'earnwall_secret_xyz';
        $reward = '50';
        $transId = 'EW-998877';

        Offerwall::create([
            'name' => 'EarnWall',
            'iframe_url_pattern' => 'https://earnwall.net/offerwall/api/{user_id}',
            'status' => true,
            'secret_key' => $secret,
            'param_user_id' => 'subId',
            'param_transaction_id' => 'transId',
            'param_amount' => 'reward',
            'param_secret_key' => 'signature',
            'reward_ratio' => 1.0,
        ]);

        // Formula: md5(subId . transId . reward . secret)
        $signature = md5($user->id . $transId . $reward . $secret);

        $response = $this->post('/postback/earnwall', [
            'subId' => $user->id,
            'transId' => $transId,
            'reward' => $reward,
            'payout' => '0.50',
            'status' => '1',
            'signature' => $signature,
        ]);

        $response->assertStatus(200);
        // CRITICAL: EarnWall requires exact 'ok' string!
        $this->assertEquals('ok', $response->getContent());
        $this->assertEquals(5000, $user->fresh()->main_balance);
    }

    public function test_moneyrain_raw_json_hmac_postback_credits_user_and_returns_ok(): void
    {
        \App\Models\AppSetting::setByKey('offerwall_pending_hours', 0);
        \App\Models\AppSetting::setByKey('conversion_rate', 100);

        $user = User::factory()->create(['main_balance' => 0]);
        $secret = 'moneyrain_callback_sec_777';
        $viewId = 98765;

        Offerwall::create([
            'name' => 'MoneyRain',
            'iframe_url_pattern' => 'https://offerwall.moneyrain.top/s/site?external_uid={user_id}',
            'status' => true,
            'secret_key' => $secret,
            'param_user_id' => 'external_uid',
            'param_transaction_id' => 'view_id',
            'param_amount' => 'reward_currency_amount',
            'reward_ratio' => 1.0,
        ]);

        $payload = [
            'event' => 'reward.completed',
            'view_id' => $viewId,
            'external_uid' => (string) $user->id,
            'ad_type' => 'ptc',
            'reward_usdt' => '0.00003000',
            'reward_currency_amount' => '50.00',
            'status' => 'completed',
            'timestamp' => time(),
            'nonce' => 'abc123nonce',
        ];

        $rawBody = json_encode($payload);
        $headerSig = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);

        $response = $this->call(
            'POST',
            '/postback/moneyrain',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_MONEYRAIN_SIGNATURE' => $headerSig,
            ],
            $rawBody
        );

        $response->assertStatus(200);
        $this->assertEquals('OK', $response->getContent());
        // Pre-converted reward_currency_amount = 50 coins
        $this->assertEquals(50, $user->fresh()->main_balance);
        $this->assertDatabaseHas('offerwall_logs', [
            'user_id' => $user->id,
            'transaction_id' => (string) $viewId,
            'provider' => 'Moneyrain',
        ]);
    }

    public function test_moneyrain_rejects_invalid_hmac_signature(): void
    {
        $user = User::factory()->create(['main_balance' => 0]);
        $secret = 'secret_correct';

        Offerwall::create([
            'name' => 'MoneyRain',
            'iframe_url_pattern' => 'https://offerwall.moneyrain.top/s/site?external_uid={user_id}',
            'status' => true,
            'secret_key' => $secret,
        ]);

        $payload = [
            'event' => 'reward.completed',
            'view_id' => 112233,
            'external_uid' => (string) $user->id,
            'reward_currency_amount' => '10.00',
        ];

        $rawBody = json_encode($payload);
        $invalidSig = 'sha256=' . hash_hmac('sha256', $rawBody, 'wrong_secret');

        $response = $this->call(
            'POST',
            '/postback/moneyrain',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_MONEYRAIN_SIGNATURE' => $invalidSig,
            ],
            $rawBody
        );

        $response->assertStatus(403);
    }

    public function test_capsbit_rejected_chargeback_reverses_balance(): void
    {
        \App\Models\AppSetting::setByKey('offerwall_pending_hours', 0);
        \App\Models\AppSetting::setByKey('conversion_rate', 100);

        $user = User::factory()->create(['main_balance' => 0]);
        $secret = 'caps_secret';
        $txid = 'CAPS_CB_1';
        $payout = '0.50';

        Offerwall::create([
            'name' => 'Capsbit',
            'iframe_url_pattern' => 'https://offerwall.capsbit.com/key/{user_id}',
            'status' => true,
            'secret_key' => $secret,
            'param_user_id' => 'uid',
            'param_transaction_id' => 'txid',
            'param_amount' => 'payout',
            'param_secret_key' => 'sig',
            'reward_ratio' => 1.0,
        ]);

        $sig = md5($user->id . $payout . '1' . $txid . $secret);

        // 1. Initial valid conversion
        $this->get("/postback/capsbit?uid={$user->id}&txid={$txid}&payout={$payout}&offer_id=1&status=approved&sig={$sig}")
            ->assertStatus(200);
        $this->assertEquals(50, $user->fresh()->main_balance);

        // 2. Chargeback conversion (status=rejected)
        $cbSig = md5($user->id . $payout . '1' . $txid . $secret);
        $cbResponse = $this->get("/postback/capsbit?uid={$user->id}&txid={$txid}&payout={$payout}&offer_id=1&status=rejected&sig={$cbSig}");
        $cbResponse->assertStatus(200);
        $this->assertEquals('OK', $cbResponse->getContent());
        $this->assertEquals(0, $user->fresh()->main_balance);
        $this->assertDatabaseHas('offerwall_logs', [
            'transaction_id' => $txid,
            'status' => 'reversed',
        ]);
    }

    public function test_earnwall_chargeback_status_2_reverses_balance(): void
    {
        \App\Models\AppSetting::setByKey('offerwall_pending_hours', 0);
        \App\Models\AppSetting::setByKey('conversion_rate', 100);

        $user = User::factory()->create(['main_balance' => 0]);
        $secret = 'ew_sec';
        $transId = 'EW-CB-99';
        $reward = '20';

        Offerwall::create([
            'name' => 'EarnWall',
            'iframe_url_pattern' => 'https://earnwall.net/offerwall/api/{user_id}',
            'status' => true,
            'secret_key' => $secret,
            'param_user_id' => 'subId',
            'param_transaction_id' => 'transId',
            'param_amount' => 'reward',
            'param_secret_key' => 'signature',
            'reward_ratio' => 1.0,
        ]);

        $sig = md5($user->id . $transId . $reward . $secret);

        // 1. Initial approval
        $this->post('/postback/earnwall', [
            'subId' => $user->id,
            'transId' => $transId,
            'reward' => $reward,
            'status' => '1',
            'signature' => $sig,
        ])->assertStatus(200);
        $this->assertEquals(2000, $user->fresh()->main_balance);

        // 2. Chargeback (status=2)
        $cbResponse = $this->post('/postback/earnwall', [
            'subId' => $user->id,
            'transId' => $transId,
            'reward' => $reward,
            'status' => '2',
            'signature' => $sig,
        ]);
        $cbResponse->assertStatus(200);
        $this->assertEquals('ok', $cbResponse->getContent());
        $this->assertEquals(0, $user->fresh()->main_balance);
        $this->assertDatabaseHas('offerwall_logs', [
            'transaction_id' => $transId,
            'status' => 'reversed',
        ]);
    }
}
