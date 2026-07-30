<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\PromoCode;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_redeem_valid_promo_code(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'main_balance' => 100,
        ]);

        $promo = PromoCode::create([
            'code' => 'WELCOME50',
            'reward_points' => 50,
            'max_uses' => 10,
            'used_count' => 0,
            'is_active' => true,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($user)->post('/promo/redeem', [
            'code' => 'WELCOME50',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(150, $user->fresh()->main_balance);
        $this->assertEquals(1, $promo->fresh()->used_count);
    }

    public function test_user_cannot_redeem_promo_code_twice(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'main_balance' => 100,
        ]);

        $promo = PromoCode::create([
            'code' => 'ONCEONLY',
            'reward_points' => 50,
            'max_uses' => 10,
            'used_count' => 0,
            'is_active' => true,
            'expires_at' => now()->addDays(7),
        ]);

        // First redemption
        $this->actingAs($user)->post('/promo/redeem', ['code' => 'ONCEONLY']);

        // Second redemption should fail
        $response = $this->actingAs($user)->post('/promo/redeem', ['code' => 'ONCEONLY']);
        $response->assertSessionHasErrors('promo_code');

        $this->assertEquals(150, $user->fresh()->main_balance);
        $this->assertEquals(1, $promo->fresh()->used_count);
    }

    public function test_withdrawal_deducts_user_balance_and_creates_record(): void
    {
        AppSetting::setByKey('first_withdraw_limit', 100);
        AppSetting::setByKey('conversion_rate', 100); // 100 coins = 1 BDT

        /** @var User $user */
        $user = User::factory()->create([
            'main_balance' => 500,
            'payment_method' => 'bkash',
            'payment_number' => '01700000000',
        ]);

        $response = $this->actingAs($user)->post('/withdraw', [
            'amount_coins' => 200,
            'payment_method' => 'bKash',
            'account_details' => '01700000000',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(300, $user->fresh()->main_balance);
        $this->assertDatabaseHas('withdrawals', [
            'user_id' => $user->id,
            'amount_coins' => 200,
            'status' => 'pending',
        ]);
    }

    public function test_user_with_health_at_or_below_40_percent_cannot_request_withdrawal(): void
    {
        AppSetting::setByKey('first_withdraw_limit', 100);
        AppSetting::setByKey('min_withdrawal_health', 40);

        /** @var User $user */
        $user = User::factory()->create([
            'main_balance' => 500,
            'health' => 40,
        ]);

        $response = $this->actingAs($user)->post('/withdraw', [
            'amount_coins' => 200,
            'payment_method' => 'bKash',
            'account_details' => '01700000000',
        ]);

        $response->assertSessionHasErrors(['message']);
        $this->assertEquals(500, $user->fresh()->main_balance);
        $this->assertDatabaseMissing('withdrawals', [
            'user_id' => $user->id,
        ]);
    }

    public function test_user_with_health_above_40_percent_can_request_withdrawal(): void
    {
        AppSetting::setByKey('first_withdraw_limit', 100);
        AppSetting::setByKey('min_withdrawal_health', 40);

        /** @var User $user */
        $user = User::factory()->create([
            'main_balance' => 500,
            'health' => 41,
        ]);

        $response = $this->actingAs($user)->post('/withdraw', [
            'amount_coins' => 200,
            'payment_method' => 'bKash',
            'account_details' => '01700000000',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(300, $user->fresh()->main_balance);
        $this->assertDatabaseHas('withdrawals', [
            'user_id' => $user->id,
            'amount_coins' => 200,
        ]);
    }
}
