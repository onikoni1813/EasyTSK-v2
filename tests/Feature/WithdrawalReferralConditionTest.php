<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\PaymentMethod;
use App\Models\ReferralTracking;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawalReferralConditionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::setByKey('conversion_rate', '1000');
        AppSetting::setByKey('first_withdraw_limit', '20000');
        AppSetting::setByKey('next_withdraw_limit', '20000');
        AppSetting::setByKey('min_withdrawal_health', '40');
        AppSetting::setByKey('min_unlocked_referrals_for_next_withdraw', '1');

        PaymentMethod::create([
            'name' => 'bKash Personal',
            'code' => 'bkash',
            'type' => 'mobile_banking',
            'min_points' => 20000,
            'is_active' => true,
        ]);
    }

    public function test_first_withdrawal_is_unconditional_with_no_referrals_required(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'main_balance' => 50000,
            'health' => 100,
        ]);

        $this->assertEquals(0, Withdrawal::where('user_id', $user->id)->count());
        $this->assertEquals(0, ReferralTracking::where('referrer_id', $user->id)->count());

        $response = $this->actingAs($user)->post('/withdraw', [
            'amount_coins' => 20000,
            'payment_method' => 'bkash',
            'account_details' => '01711223344',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('withdrawals', [
            'user_id' => $user->id,
            'amount_coins' => 20000,
            'status' => 'pending',
        ]);
    }

    public function test_second_withdrawal_fails_if_user_has_no_unlocked_referrals(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'main_balance' => 50000,
            'health' => 100,
            'last_withdrawal_at' => now()->subHours(25), // Cooldown passed
        ]);

        // Prior completed withdrawal exists
        Withdrawal::create([
            'user_id' => $user->id,
            'amount_coins' => 20000,
            'amount_bdt' => 20,
            'payment_method' => 'bKash Personal',
            'account_details' => '01711223344',
            'status' => 'paid',
        ]);

        $response = $this->actingAs($user)->post('/withdraw', [
            'amount_coins' => 20000,
            'payment_method' => 'bkash',
            'account_details' => '01711223344',
        ]);

        $response->assertSessionHasErrors(['message']);
        $this->assertStringContainsString('unlocked active referral', session('errors')->first('message'));
        $this->assertEquals(50000, $user->fresh()->main_balance);
    }

    public function test_second_withdrawal_fails_if_referral_is_still_locked(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'main_balance' => 50000,
            'health' => 100,
            'last_withdrawal_at' => now()->subHours(25),
        ]);

        Withdrawal::create([
            'user_id' => $user->id,
            'amount_coins' => 20000,
            'amount_bdt' => 20,
            'payment_method' => 'bKash Personal',
            'account_details' => '01711223344',
            'status' => 'paid',
        ]);

        $referred = User::factory()->create(['ref_by' => $user->id]);

        // Referral is still locked (has not completed enough tasks)
        ReferralTracking::create([
            'referrer_id' => $user->id,
            'referred_user_id' => $referred->id,
            'locked_reward' => 500,
            'target_amount' => 1000,
            'earned_so_far' => 200,
            'status' => 'locked',
        ]);

        $response = $this->actingAs($user)->post('/withdraw', [
            'amount_coins' => 20000,
            'payment_method' => 'bkash',
            'account_details' => '01711223344',
        ]);

        $response->assertSessionHasErrors(['message']);
        $this->assertStringContainsString('unlocked active referral', session('errors')->first('message'));
    }

    public function test_second_withdrawal_succeeds_when_user_has_unlocked_referral(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'main_balance' => 50000,
            'health' => 100,
            'last_withdrawal_at' => now()->subHours(25),
        ]);

        Withdrawal::create([
            'user_id' => $user->id,
            'amount_coins' => 20000,
            'amount_bdt' => 20,
            'payment_method' => 'bKash Personal',
            'account_details' => '01711223344',
            'status' => 'paid',
        ]);

        $referred = User::factory()->create(['ref_by' => $user->id]);

        // Referral has completed enough tasks and is UNLOCKED
        ReferralTracking::create([
            'referrer_id' => $user->id,
            'referred_user_id' => $referred->id,
            'locked_reward' => 500,
            'target_amount' => 1000,
            'earned_so_far' => 1000,
            'status' => 'unlocked',
        ]);

        $response = $this->actingAs($user)->post('/withdraw', [
            'amount_coins' => 20000,
            'payment_method' => 'bkash',
            'account_details' => '01711223344',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(30000, $user->fresh()->main_balance);
        $this->assertEquals(2, Withdrawal::where('user_id', $user->id)->count());
    }

    public function test_admin_setting_zero_disables_referral_requirement(): void
    {
        AppSetting::setByKey('min_unlocked_referrals_for_next_withdraw', '0');

        /** @var User $user */
        $user = User::factory()->create([
            'main_balance' => 50000,
            'health' => 100,
            'last_withdrawal_at' => now()->subHours(25),
        ]);

        Withdrawal::create([
            'user_id' => $user->id,
            'amount_coins' => 20000,
            'amount_bdt' => 20,
            'payment_method' => 'bKash Personal',
            'account_details' => '01711223344',
            'status' => 'paid',
        ]);

        // Has 0 referrals, but setting is 0, so withdrawal succeeds
        $response = $this->actingAs($user)->post('/withdraw', [
            'amount_coins' => 20000,
            'payment_method' => 'bkash',
            'account_details' => '01711223344',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(30000, $user->fresh()->main_balance);
    }

    public function test_withdraw_page_shares_accurate_referral_props(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'main_balance' => 50000,
            'health' => 100,
        ]);

        $response = $this->actingAs($user)->get('/withdraw');
        $response->assertStatus(200);

        $props = $response->viewData('page')['props'];
        $this->assertTrue($props['isFirstWithdrawal']);
        $this->assertTrue($props['referralRequirementMet']);
        $this->assertEquals(1, $props['minUnlockedReferralsRequired']);
        $this->assertEquals(0, $props['unlockedReferralsCount']);
        $this->assertNotEmpty($props['referralCode']);

        // Now simulate 1st withdrawal completed
        Withdrawal::create([
            'user_id' => $user->id,
            'amount_coins' => 20000,
            'amount_bdt' => 20,
            'payment_method' => 'bKash Personal',
            'account_details' => '01711223344',
            'status' => 'paid',
        ]);

        $response2 = $this->actingAs($user)->get('/withdraw');
        $props2 = $response2->viewData('page')['props'];
        $this->assertFalse($props2['isFirstWithdrawal']);
        $this->assertFalse($props2['referralRequirementMet']);
        $this->assertFalse($props2['canWithdraw']);
    }

    public function test_admin_can_update_unlocked_referral_setting(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $adminPath = config('app.admin_path', 'secret-panel');

        $response = $this->actingAs($admin)->post("/{$adminPath}/settings", [
            'conversion_rate' => 1000,
            'welcome_bonus' => 50,
            'happy_hour' => false,
            'first_withdraw_limit' => 20000,
            'next_withdraw_limit' => 20000,
            'min_unlocked_referrals_for_next_withdraw' => 2,
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
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('2', AppSetting::getByKey('min_unlocked_referrals_for_next_withdraw'));
    }
}
