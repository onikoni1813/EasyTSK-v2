<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\PaymentMethod;
use App\Models\PhoneVerification;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhoneVerificationWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'user',
            'main_balance' => 5000,
            'health' => 100,
            'phone' => '01712345678',
            'phone_verified_at' => null,
        ]);

        $this->paymentMethod = PaymentMethod::where('code', 'bKash')->first() ?? PaymentMethod::create([
            'name' => 'bKash Personal',
            'code' => 'bKash',
            'type' => 'mobile_banking',
            'currency' => 'BDT',
            'currency_symbol' => '৳',
            'conversion_rate' => 100,
            'min_points' => 1000,
            'fixed_charge' => 0,
            'charge_percent' => 0,
            'account_placeholder' => '017XXXXXXXX',
            'is_active' => true,
            'order' => 1,
        ]);

        AppSetting::setByKey('bulksmsdhaka_api_key', 'test_dummy_api_key');
        AppSetting::setByKey('first_withdraw_limit', 1000);
        AppSetting::setByKey('next_withdraw_limit', 500);
    }

    public function test_withdraw_page_shares_phone_qualification_props(): void
    {
        AppSetting::setByKey('bulksmsdhaka_enabled', 'true');

        $response = $this->actingAs($this->user)->get(route('withdraw.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Withdraw/Index')
            ->where('smsGatewayActive', true)
            ->where('isPhoneVerified', false)
            ->where('userPhone', '01712345678')
        );
    }

    public function test_unverified_user_cannot_withdraw_when_sms_gateway_is_active(): void
    {
        AppSetting::setByKey('bulksmsdhaka_enabled', 'true');

        $response = $this->actingAs($this->user)->post(route('withdraw.request'), [
            'amount_coins' => 1000,
            'payment_method' => 'bKash Personal',
            'account_details' => '01712345678',
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('withdrawals', 0);
        $this->assertEquals(5000, $this->user->fresh()->main_balance);
    }

    public function test_unverified_user_can_withdraw_if_sms_gateway_is_disabled(): void
    {
        AppSetting::setByKey('bulksmsdhaka_enabled', 'false');

        $response = $this->actingAs($this->user)
            ->from(route('withdraw.index'))
            ->post(route('withdraw.request'), [
                'amount_coins' => 1000,
                'payment_method' => 'bKash Personal',
                'account_details' => '01712345678',
            ]);

        $response->assertRedirect(route('withdraw.index'));
        $this->assertDatabaseCount('withdrawals', 1);
        $this->assertEquals(4000, $this->user->fresh()->main_balance);
    }

    public function test_user_cannot_send_otp_with_invalid_phone_number(): void
    {
        $response = $this->actingAs($this->user)->post(route('verification.phone.send-otp'), [
            'phone' => '12345',
        ]);

        $response->assertSessionHasErrors(['phone']);
    }

    public function test_user_cannot_send_otp_with_phone_verified_by_another_user(): void
    {
        User::factory()->create([
            'phone' => '01799999999',
            'phone_verified_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->post(route('verification.phone.send-otp'), [
            'phone' => '01799999999',
        ]);

        $response->assertSessionHasErrors(['phone']);
        $this->assertDatabaseCount('phone_verifications', 0);
    }

    public function test_user_can_send_otp_successfully(): void
    {
        Http::fake([
            'https://bulksmsdhaka.net/*' => Http::response(['Status' => 0, 'Message' => 'SMS Sent Successfully'], 200),
            'https://bulksmsdhaka.com/*' => Http::response(['Status' => 0, 'Message' => 'SMS Sent Successfully'], 200),
        ]);

        $response = $this->actingAs($this->user)->post(route('verification.phone.send-otp'), [
            'phone' => '01812345678',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('phone_verifications', [
            'user_id' => $this->user->id,
            'phone' => '01812345678',
        ]);

        $verification = PhoneVerification::where('user_id', $this->user->id)->first();
        $this->assertNotNull($verification);
        $this->assertEquals(6, strlen($verification->otp_code));
        $this->assertEquals('01812345678', $this->user->fresh()->phone);
    }

    public function test_user_hits_cooldown_if_requesting_otp_repeatedly(): void
    {
        PhoneVerification::create([
            'user_id' => $this->user->id,
            'phone' => '01712345678',
            'otp_code' => '112233',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->post(route('verification.phone.send-otp'), [
            'phone' => '01712345678',
        ]);

        $response->assertSessionHasErrors(['otp']);
    }

    public function test_user_cannot_verify_with_wrong_code(): void
    {
        PhoneVerification::create([
            'user_id' => $this->user->id,
            'phone' => '01712345678',
            'otp_code' => '555666',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->actingAs($this->user)->post(route('verification.phone.verify-otp'), [
            'otp_code' => '111222',
        ]);

        $response->assertSessionHasErrors(['otp_code']);
        $verification = PhoneVerification::where('user_id', $this->user->id)->first();
        $this->assertEquals(1, $verification->attempts);
        $this->assertNull($this->user->fresh()->phone_verified_at);
    }

    public function test_user_cannot_verify_with_expired_code(): void
    {
        PhoneVerification::create([
            'user_id' => $this->user->id,
            'phone' => '01712345678',
            'otp_code' => '555666',
            'attempts' => 0,
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($this->user)->post(route('verification.phone.verify-otp'), [
            'otp_code' => '555666',
        ]);

        $response->assertSessionHasErrors(['otp_code']);
        $this->assertNull($this->user->fresh()->phone_verified_at);
    }

    public function test_user_can_verify_correct_otp_and_qualify_account(): void
    {
        PhoneVerification::create([
            'user_id' => $this->user->id,
            'phone' => '01712345678',
            'otp_code' => '789123',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->actingAs($this->user)->post(route('verification.phone.verify-otp'), [
            'otp_code' => '789123',
        ]);

        $response->assertSessionHas('success');

        $this->user->refresh();
        $this->assertNotNull($this->user->phone_verified_at);
        $this->assertTrue($this->user->isPhoneVerified());

        $verification = PhoneVerification::where('user_id', $this->user->id)->first();
        $this->assertNotNull($verification->verified_at);
    }

    public function test_qualified_user_can_withdraw_when_sms_gateway_is_active(): void
    {
        AppSetting::setByKey('bulksmsdhaka_enabled', 'true');
        $this->user->update(['phone_verified_at' => now()]);

        $response = $this->actingAs($this->user)
            ->from(route('withdraw.index'))
            ->post(route('withdraw.request'), [
                'amount_coins' => 1000,
                'payment_method' => 'bKash Personal',
                'account_details' => '01712345678',
            ]);

        $response->assertRedirect(route('withdraw.index'));
        $this->assertDatabaseCount('withdrawals', 1);
        $this->assertEquals(4000, $this->user->fresh()->main_balance);
    }
}
