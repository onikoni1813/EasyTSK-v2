<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignService;
use App\Models\Task;
use App\Models\User;
use App\Models\UserTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommunityCampaignFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_3_tier_progression_gating_logic(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['health' => 100, 'main_balance' => 500]);
        $creator = User::factory()->create(['health' => 100, 'main_balance' => 1000]);

        $service = CampaignService::create([
            'platform' => 'Telegram',
            'action' => 'join',
            'clicker_reward' => 5.00,
            'creator_cost' => 10.00,
            'min_clicks' => 5,
            'max_clicks' => 100,
            'is_active' => true,
        ]);

        $officialTask = Task::create([
            'title' => 'Official Task 1',
            'type' => 'shortlink',
            'reward_coins' => 10,
            'reward_xp' => 10,
            'status' => 'active',
            'cooldown_hours' => 0,
        ]);

        $campaign = Campaign::create([
            'user_id' => $creator->id,
            'campaign_service_id' => $service->id,
            'title' => 'Community Channel Join',
            'target_url' => 'https://t.me/testchannel',
            'type' => 'Telegram',
            'action' => 'join',
            'proof_type' => 'secret_code',
            'secret_code' => 'COMMUNITY999',
            'budget_points' => 50,
            'cost_per_click' => 5.00,
            'target_clicks' => 10,
            'total_clicks' => 0,
            'status' => 'active',
        ]);

        // 1. Initial State: Official task is incomplete -> Community & Offerwall locked
        $response = $this->actingAs($user)->get('/tasks');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Tasks/Index')
            ->where('community_locked', true)
            ->where('pending_system_tasks_count', 1)
            ->where('is_locked', true)
        );

        // Submitting campaign proof while locked should be blocked
        $blockedSubmit = $this->actingAs($user)->post("/tasks/campaign/{$campaign->id}/submit", [
            'secret_code' => 'COMMUNITY999',
        ]);
        $blockedSubmit->assertSessionHasErrors(['message']);

        // 2. Submit the Official Task (Status is 'pending' awaiting admin review)
        $completedTask = UserTask::create([
            'user_id' => $user->id,
            'task_id' => $officialTask->id,
            'status' => 'pending',
            'ip_address' => '127.0.0.1',
        ]);
        $completedTask->created_at = now()->subMinutes(5);
        $completedTask->save();

        // 3. Now Community is UNLOCKED, Offerwall remains locked until community campaign is done
        $response2 = $this->actingAs($user)->get('/tasks');
        $response2->assertInertia(fn ($page) => $page
            ->where('community_locked', false)
            ->where('pending_system_tasks_count', 0)
            ->where('is_locked', true)
            ->where('community_pending_count', 1)
        );

        // 4. Submit Community Task with correct code -> Instant auto-approval
        $validSubmit = $this->actingAs($user)->post("/tasks/campaign/{$campaign->id}/submit", [
            'secret_code' => 'COMMUNITY999',
        ]);
        $validSubmit->assertSessionHas('success');

        $this->assertEquals(505.00, $user->fresh()->main_balance);
        $this->assertEquals(1, $campaign->fresh()->total_clicks);

        // 5. Check History page displays community campaign with correct title and points
        $historyRes = $this->actingAs($user)->get('/tasks-history');
        $historyRes->assertStatus(200);
        $historyRes->assertInertia(fn ($page) => $page
            ->component('Tasks/History')
            ->where('taskHistory.data.0.task_title', 'Community Channel Join')
            ->where('taskHistory.data.0.task_type', 'community')
            ->where('taskHistory.data.0.reward_coins', 5)
            ->where('taskHistory.data.0.status', 'approved')
        );

        // 6. Both Tier 1 & Tier 2 complete -> Offerwall is now UNLOCKED!
        $response3 = $this->actingAs($user)->get('/tasks');
        $response3->assertInertia(fn ($page) => $page
            ->where('community_locked', false)
            ->where('pending_system_tasks_count', 0)
            ->where('is_locked', false)
            ->where('pending_tasks_count', 0)
        );
    }

    public function test_incorrect_secret_code_deducts_health(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['health' => 100, 'main_balance' => 100]);
        $creator = User::factory()->create(['health' => 100]);

        $service = CampaignService::create([
            'platform' => 'Telegram',
            'action' => 'join',
            'clicker_reward' => 5.00,
            'creator_cost' => 10.00,
            'min_clicks' => 5,
            'max_clicks' => 100,
            'is_active' => true,
        ]);

        $campaign = Campaign::create([
            'user_id' => $creator->id,
            'campaign_service_id' => $service->id,
            'title' => 'Secret Test',
            'target_url' => 'https://t.me/test',
            'type' => 'Telegram',
            'action' => 'join',
            'proof_type' => 'secret_code',
            'secret_code' => 'RIGHTCODE',
            'budget_points' => 50,
            'cost_per_click' => 5.00,
            'target_clicks' => 10,
            'total_clicks' => 0,
            'status' => 'active',
        ]);

        $res = $this->actingAs($user)->post("/tasks/campaign/{$campaign->id}/submit", [
            'secret_code' => 'WRONGCODE',
        ]);

        $res->assertSessionHasErrors(['message']);
        $this->assertEquals(90, $user->fresh()->health);
    }

    public function test_user_cannot_submit_own_campaign(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['health' => 100, 'main_balance' => 500]);

        $service = CampaignService::create([
            'platform' => 'Telegram',
            'action' => 'join',
            'clicker_reward' => 5.00,
            'creator_cost' => 10.00,
            'min_clicks' => 5,
            'max_clicks' => 100,
            'is_active' => true,
        ]);

        $campaign = Campaign::create([
            'user_id' => $user->id, // Created by same user
            'campaign_service_id' => $service->id,
            'title' => 'My Own Campaign',
            'target_url' => 'https://t.me/test',
            'type' => 'Telegram',
            'action' => 'join',
            'proof_type' => 'secret_code',
            'secret_code' => 'ANYCODE',
            'budget_points' => 50,
            'cost_per_click' => 5.00,
            'target_clicks' => 10,
            'total_clicks' => 0,
            'status' => 'active',
        ]);

        $res = $this->actingAs($user)->post("/tasks/campaign/{$campaign->id}/submit", [
            'secret_code' => 'ANYCODE',
        ]);

        $res->assertSessionHasErrors(['message']);
    }
}
