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
}
