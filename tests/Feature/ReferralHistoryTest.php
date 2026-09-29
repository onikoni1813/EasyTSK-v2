<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\ReferralTracking;
use App\Models\User;
use App\Models\UserTask;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_fetch_referral_history_json_endpoint(): void
    {
        $referrer = User::factory()->create();
        $referredUser = User::factory()->create([
            'name' => 'John Referral',
            'phone' => '01700000001',
        ]);

        $tracking = ReferralTracking::create([
            'referrer_id'      => $referrer->id,
            'referred_user_id' => $referredUser->id,
            'locked_reward'    => 500,
            'target_amount'    => 1000,
            'earned_so_far'    => 250,
            'status'           => 'locked',
        ]);

        $response = $this->actingAs($referrer)->getJson(route('referrals.history'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'total',
        ]);

        $this->assertEquals(1, $response->json('total'));
        $item = $response->json('data.0');
        $this->assertEquals($tracking->id, $item['id']);
        $this->assertEquals('John Referral', $item['referred_user']['name']);
        $this->assertEquals(500, $item['locked_reward']);
        $this->assertEquals(1000, $item['target_amount']);
        $this->assertEquals(250, $item['earned_so_far']);
        $this->assertEquals('locked', $item['status']);
    }

    public function test_referral_history_handles_deleted_user_gracefully(): void
    {
        $referrer = User::factory()->create();
        $referred = User::factory()->create();

        ReferralTracking::create([
            'referrer_id'      => $referrer->id,
            'referred_user_id' => $referred->id,
            'locked_reward'    => 500,
            'target_amount'    => 1000,
            'earned_so_far'    => 0,
            'status'           => 'locked',
        ]);

        $response = $this->actingAs($referrer)->getJson(route('referrals.history'));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('total'));
        $this->assertNotNull($response->json('data.0.referred_user'));
    }

    public function test_user_can_access_full_referral_page(): void
    {
        $referrer = User::factory()->create();

        $response = $this->actingAs($referrer)->get('/reffer');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Referrals/Index')
            ->has('referrals')
            ->has('stats')
            ->has('user')
            ->has('referral_bonus')
            ->has('referral_target')
        );
    }

    public function test_referral_bonus_unlocks_when_referred_user_earns_target(): void
    {
        AppSetting::setByKey('referral_bonus', '500');
        AppSetting::setByKey('referral_target', '1000');

        $referrer = User::factory()->create([
            'main_balance'   => 0,
            'locked_balance' => 0,
        ]);

        $referredUser = User::factory()->create();

        $service = app(ReferralService::class);
        $service->setupNewReferral($referredUser, $referrer->id);

        $referrer->refresh();
        $this->assertEquals(500, (float) $referrer->locked_balance);
        $this->assertEquals(0, (float) $referrer->main_balance);

        // Step 1: Earn partial amount (400)
        $service->recordReferredUserEarning($referredUser, 400);

        $tracking = ReferralTracking::where('referrer_id', $referrer->id)->first();
        $this->assertEquals('locked', $tracking->status);
        $this->assertEquals(400, (float) $tracking->earned_so_far);

        // Step 2: Earn remaining amount to reach 1000
        $service->recordReferredUserEarning($referredUser, 600);

        $tracking->refresh();
        $referrer->refresh();

        $this->assertEquals('unlocked', $tracking->status);
        $this->assertEquals(1000, (float) $tracking->earned_so_far);
        $this->assertEquals(0, (float) $referrer->locked_balance);
        $this->assertEquals(500, (float) $referrer->main_balance);
    }

    public function test_admin_can_view_user_referral_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $referred = User::factory()->create();

        ReferralTracking::create([
            'referrer_id'      => $user->id,
            'referred_user_id' => $referred->id,
            'locked_reward'    => 500,
            'target_amount'    => 1000,
            'earned_so_far'    => 100,
            'status'           => 'locked',
        ]);

        $response = $this->actingAs($admin)->get("/secret-panel/users/{$user->id}/history");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'stats',
            'tasks',
            'referrals',
            'withdrawals',
        ]);

        $this->assertCount(1, $response->json('referrals'));
        $this->assertEquals(500, $response->json('referrals.0.locked_reward'));
    }
}
