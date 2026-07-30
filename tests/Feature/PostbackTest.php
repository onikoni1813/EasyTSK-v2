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
}
