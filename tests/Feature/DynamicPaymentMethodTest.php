<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed default methods as migration would do
        if (PaymentMethod::count() === 0) {
            PaymentMethod::create([
                'name' => 'bKash Personal',
                'code' => 'bKash',
                'type' => 'mobile_banking',
                'min_points' => 1000,
                'fixed_charge' => 0,
                'charge_percent' => 0,
                'account_placeholder' => '017XXXXXXXX',
                'is_active' => true,
                'order' => 1,
            ]);
            PaymentMethod::create([
                'name' => 'Nagad Personal',
                'code' => 'Nagad',
                'type' => 'mobile_banking',
                'min_points' => 1000,
                'fixed_charge' => 0,
                'charge_percent' => 0,
                'account_placeholder' => '017XXXXXXXX',
                'is_active' => true,
                'order' => 2,
            ]);
        }
    }

    public function test_admin_can_view_payment_methods_index(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/secret-panel/payment-methods');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_new_payment_method(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post('/secret-panel/payment-methods', [
            'name' => 'Binance USDT',
            'code' => 'USDT',
            'type' => 'crypto',
            'currency' => 'USDT',
            'currency_symbol' => '$',
            'conversion_rate' => 12000,
            'min_points' => 2000,
            'fixed_charge' => 50,
            'charge_percent' => 1.5,
            'account_placeholder' => 'Enter TRC20 Address',
            'instructions' => 'Only TRC20 network supported.',
            'icon' => '🟡',
            'is_active' => true,
            'order' => 5,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payment_methods', [
            'code' => 'USDT',
            'name' => 'Binance USDT',
            'type' => 'crypto',
            'currency' => 'USDT',
            'currency_symbol' => '$',
            'min_points' => 2000,
        ]);
    }

    public function test_admin_can_toggle_payment_method_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $method = PaymentMethod::where('code', 'bKash')->first();
        $this->assertTrue($method->is_active);

        $response = $this->actingAs($admin)->post("/secret-panel/payment-methods/{$method->id}/toggle");
        $response->assertSessionHasNoErrors();

        $this->assertFalse($method->fresh()->is_active);
    }

    public function test_admin_can_update_payment_method(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $method = PaymentMethod::where('code', 'bKash')->first();

        $response = $this->actingAs($admin)->put("/secret-panel/payment-methods/{$method->id}", [
            'name' => 'bKash Personal (Updated)',
            'code' => 'bKash',
            'type' => 'mobile_banking',
            'currency' => 'BDT',
            'currency_symbol' => '৳',
            'min_points' => 1500,
            'fixed_charge' => 5,
            'charge_percent' => 1.0,
            'account_placeholder' => '01XXXXXXXXX',
            'is_active' => true,
            'order' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('bKash Personal (Updated)', $method->fresh()->name);
        $this->assertEquals(1500, $method->fresh()->min_points);
    }

    public function test_admin_can_delete_payment_method_without_pending_withdrawals(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $method = PaymentMethod::create([
            'name' => 'Custom Delete Test',
            'code' => 'custom_delete_test',
            'type' => 'mobile_banking',
            'currency' => 'BDT',
            'currency_symbol' => '৳',
            'min_points' => 1000,
            'account_placeholder' => '017XXXXXXXX',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete("/secret-panel/payment-methods/{$method->id}");
        $response->assertSessionHasNoErrors();
        $this->assertNull(PaymentMethod::find($method->id));
    }

    public function test_disabled_method_is_hidden_from_user_withdraw_page(): void
    {
        $user = User::factory()->create([
            'health' => 100,
            'main_balance' => 5000,
        ]);

        // Disable Nagad
        PaymentMethod::where('code', 'Nagad')->update(['is_active' => false]);

        $response = $this->actingAs($user)->get('/withdraw');
        $response->assertStatus(200);
        
        $paymentMethods = $response->viewData('page')['props']['paymentMethods'];
        $codes = collect($paymentMethods)->pluck('code')->toArray();

        $this->assertContains('bKash', $codes);
        $this->assertNotContains('Nagad', $codes);
    }

    public function test_user_cannot_withdraw_via_disabled_method(): void
    {
        $user = User::factory()->create([
            'health' => 100,
            'main_balance' => 5000,
        ]);

        PaymentMethod::where('code', 'Nagad')->update(['is_active' => false]);

        $response = $this->actingAs($user)->post('/withdraw', [
            'amount_coins' => 1000,
            'payment_method' => 'Nagad',
            'account_details' => '01712345678',
        ]);

        $response->assertSessionHasErrors('payment_method');
        $this->assertEquals(0, Withdrawal::count());
    }

    public function test_user_can_withdraw_via_newly_added_dynamic_method(): void
    {
        $user = User::factory()->create([
            'health' => 100,
            'main_balance' => 5000,
        ]);

        PaymentMethod::create([
            'name' => 'Upay Personal',
            'code' => 'Upay',
            'type' => 'mobile_banking',
            'min_points' => 500,
            'fixed_charge' => 0,
            'charge_percent' => 0,
            'account_placeholder' => '017XXXXXXXX',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/withdraw', [
            'amount_coins' => 1000,
            'payment_method' => 'Upay',
            'account_details' => '01799887766',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('withdrawals', [
            'user_id' => $user->id,
            'payment_method' => 'Upay Personal',
            'amount_coins' => 1000,
            'status' => 'pending',
        ]);
        $this->assertEquals(4000, $user->fresh()->main_balance);
    }

    public function test_user_can_withdraw_in_crypto_usdt_with_custom_rate_and_currency(): void
    {
        $user = User::factory()->create([
            'health' => 100,
            'main_balance' => 25000,
        ]);

        PaymentMethod::create([
            'name' => 'Binance Pay (USDT)',
            'code' => 'USDT',
            'type' => 'crypto',
            'currency' => 'USDT',
            'currency_symbol' => '$',
            'conversion_rate' => 12000, // 12,000 Pts = 1 USDT
            'min_points' => 12000,
            'fixed_charge' => 0,
            'charge_percent' => 0,
            'account_placeholder' => 'Binance Pay ID',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/withdraw', [
            'amount_coins' => 24000, // Should convert to 2.00 USDT
            'payment_method' => 'USDT',
            'account_details' => 'binance_user_9988',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('withdrawals', [
            'user_id' => $user->id,
            'payment_method' => 'Binance Pay (USDT)',
            'amount_coins' => 24000,
            'amount_bdt' => 2.00, // Payout amount in currency
            'currency' => 'USDT',
            'currency_symbol' => '$',
            'status' => 'pending',
        ]);
        $this->assertEquals(1000, $user->fresh()->main_balance);
    }
}
