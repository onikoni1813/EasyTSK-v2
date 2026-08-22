<?php

namespace Tests\Feature;

use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPromoCodeTest extends TestCase
{
    use RefreshDatabase;

    private string $adminPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminPath = '/' . env('ADMIN_PATH', 'secret-panel');
    }

    public function test_admin_can_access_promo_codes_page(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get($this->adminPath . '/promo-codes');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/PromoCodes'));
    }

    public function test_non_admin_cannot_access_promo_codes_page(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get($this->adminPath . '/promo-codes');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_promo_code_with_auto_generated_code(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post($this->adminPath . '/promo-codes', [
            'reward_points' => 100,
            'max_uses'      => 50,
            'description'   => 'Auto-generated welcome bonus',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('promo_codes', 1);

        $promo = PromoCode::first();
        $this->assertNotNull($promo->code);
        $this->assertEquals(8, strlen($promo->code));
        $this->assertEquals(100, $promo->reward_points);
        $this->assertEquals(50, $promo->max_uses);
        $this->assertTrue($promo->is_active);
    }

    public function test_admin_can_create_promo_code_expiring_today_and_user_can_redeem_it(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        /** @var User $user */
        $user = User::factory()->create(['role' => 'user', 'main_balance' => 0]);

        $todayDate = now()->format('Y-m-d');

        $response = $this->actingAs($admin)->post($this->adminPath . '/promo-codes', [
            'code'          => 'todaybonus',
            'description'   => 'Valid through end of today',
            'reward_points' => 50,
            'max_uses'      => 10,
            'expires_at'    => $todayDate,
        ]);

        $response->assertSessionHasNoErrors();

        $promo = PromoCode::where('code', 'TODAYBONUS')->first();
        $this->assertNotNull($promo);
        $this->assertEquals(now()->endOfDay()->format('Y-m-d H:i:s'), $promo->expires_at->format('Y-m-d H:i:s'));
        $this->assertTrue($promo->isAvailable());

        // Now user redeems it
        $redeemResponse = $this->actingAs($user)->post('/promo/redeem', [
            'code' => 'TODAYBONUS',
        ]);

        $redeemResponse->assertSessionHasNoErrors();
        $this->assertEquals(50, $user->fresh()->main_balance);
        $this->assertEquals(1, $promo->fresh()->used_count);
    }

    public function test_admin_can_toggle_promo_code_active_status(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);

        $promo = PromoCode::create([
            'code'          => 'TOGGLE100',
            'reward_points' => 100,
            'max_uses'      => 10,
            'is_active'     => true,
        ]);

        // Toggle off
        $response = $this->actingAs($admin)->post($this->adminPath . "/promo-codes/{$promo->id}/toggle");
        $response->assertSessionHasNoErrors();
        $this->assertFalse($promo->fresh()->is_active);

        // Toggle back on
        $response = $this->actingAs($admin)->post($this->adminPath . "/promo-codes/{$promo->id}/toggle");
        $response->assertSessionHasNoErrors();
        $this->assertTrue($promo->fresh()->is_active);
    }

    public function test_admin_can_delete_promo_code(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);

        $promo = PromoCode::create([
            'code'          => 'DELETE100',
            'reward_points' => 100,
            'max_uses'      => 10,
            'is_active'     => true,
        ]);

        $response = $this->actingAs($admin)->delete($this->adminPath . "/promo-codes/{$promo->id}");

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('promo_codes', ['id' => $promo->id]);
    }

    public function test_user_cannot_redeem_same_promo_code_twice(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'user', 'main_balance' => 100]);

        $promo = PromoCode::create([
            'code'          => 'ONCEONLY',
            'reward_points' => 50,
            'max_uses'      => 10,
            'is_active'     => true,
        ]);

        // First attempt: succeeds
        $res1 = $this->actingAs($user)->post('/promo/redeem', ['code' => 'ONCEONLY']);
        $res1->assertSessionHasNoErrors();
        $this->assertEquals(150, $user->fresh()->main_balance);

        // Second attempt: fails
        $res2 = $this->actingAs($user)->post('/promo/redeem', ['code' => 'ONCEONLY']);
        $res2->assertSessionHasErrors(['promo_code']);
        $this->assertEquals(150, $user->fresh()->main_balance);
    }

    public function test_user_cannot_redeem_expired_promo_code(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'user', 'main_balance' => 0]);

        PromoCode::create([
            'code'          => 'EXPIRED',
            'reward_points' => 50,
            'max_uses'      => 10,
            'expires_at'    => now()->subDay(),
            'is_active'     => true,
        ]);

        $res = $this->actingAs($user)->post('/promo/redeem', ['code' => 'EXPIRED']);
        $res->assertSessionHasErrors(['promo_code']);
        $this->assertEquals(0, $user->fresh()->main_balance);
    }

    public function test_user_cannot_redeem_disabled_promo_code(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'user', 'main_balance' => 0]);

        PromoCode::create([
            'code'          => 'DISABLED',
            'reward_points' => 50,
            'max_uses'      => 10,
            'is_active'     => false,
        ]);

        $res = $this->actingAs($user)->post('/promo/redeem', ['code' => 'DISABLED']);
        $res->assertSessionHasErrors(['promo_code']);
        $this->assertEquals(0, $user->fresh()->main_balance);
    }

    public function test_user_cannot_redeem_promo_code_when_max_uses_reached(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'user', 'main_balance' => 0]);

        PromoCode::create([
            'code'          => 'MAXREACHED',
            'reward_points' => 50,
            'max_uses'      => 2,
            'used_count'    => 2,
            'is_active'     => true,
        ]);

        $res = $this->actingAs($user)->post('/promo/redeem', ['code' => 'MAXREACHED']);
        $res->assertSessionHasErrors(['promo_code']);
        $this->assertEquals(0, $user->fresh()->main_balance);
    }

    public function test_promo_code_redemption_records_correct_transaction_log(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'user', 'main_balance' => 200]);

        $promo = PromoCode::create([
            'code'          => 'TRANSACTLOG',
            'reward_points' => 75,
            'max_uses'      => 10,
            'is_active'     => true,
        ]);

        $res = $this->actingAs($user)->post('/promo/redeem', ['code' => 'TRANSACTLOG']);
        $res->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertEquals(275, $user->main_balance);

        $tx = \App\Models\Transaction::where('user_id', $user->id)
            ->where('reference_type', 'promo')
            ->first();

        $this->assertNotNull($tx);
        $this->assertEquals(200, (float)$tx->balance_before);
        $this->assertEquals(275, (float)$tx->balance_after);
        $this->assertEquals(75, (float)$tx->amount);
    }
}

