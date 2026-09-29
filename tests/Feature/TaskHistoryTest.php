<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignService;
use App\Models\Task;
use App\Models\User;
use App\Models\UserTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_task_history(): void
    {
        $response = $this->get('/tasks-history');
        $response->assertRedirect('/login');
    }

    public function test_tasks_page_shows_empty_history_for_new_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/tasks');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Tasks/Index')
            ->has('taskHistory', 0)
        );
    }

    public function test_tasks_page_shows_last_5_tasks_in_descending_order(): void
    {
        $user = User::factory()->create();

        $task1 = Task::create([
            'title' => 'Shortlink Task 1',
            'type' => 'shortlink',
            'reward_coins' => 10,
            'status' => 'active',
        ]);

        $task2 = Task::create([
            'title' => 'Secret Code Task 2',
            'type' => 'secret_code',
            'reward_coins' => 20,
            'status' => 'active',
        ]);

        $service = CampaignService::create([
            'platform' => 'Telegram',
            'action' => 'join',
            'clicker_reward' => 15.00,
            'creator_cost' => 20.00,
            'min_clicks' => 5,
            'max_clicks' => 100,
            'is_active' => true,
        ]);

        $campaign = Campaign::create([
            'user_id' => User::factory()->create()->id,
            'campaign_service_id' => $service->id,
            'title' => 'Community Telegram Join',
            'target_url' => 'https://t.me/example',
            'type' => 'Telegram',
            'action' => 'join',
            'proof_type' => 'secret_code',
            'budget_points' => 150,
            'cost_per_click' => 15.00,
            'target_clicks' => 10,
            'total_clicks' => 0,
            'status' => 'active',
        ]);

        // Create 7 user tasks with ascending timestamps
        for ($i = 1; $i <= 7; $i++) {
            $ut = UserTask::create([
                'user_id' => $user->id,
                'task_id' => ($i % 2 === 0) ? $task2->id : $task1->id,
                'status' => ($i % 3 === 0) ? 'rejected' : 'approved',
                'admin_note' => ($i % 3 === 0) ? 'Invalid screenshot proof' : null,
            ]);
            $ut->created_at = now()->subMinutes(10 - $i);
            $ut->save();
        }

        // Add 1 community campaign task as the most recent
        $utCommunity = UserTask::create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'status' => 'pending',
        ]);
        $utCommunity->created_at = now();
        $utCommunity->save();

        $response = $this->actingAs($user)->get('/tasks');
        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page
            ->component('Tasks/Index')
            ->has('taskHistory', 5) // Exactly last 5
            ->where('taskHistory.0.task_title', 'Community Telegram Join')
            ->where('taskHistory.0.task_type', 'community')
            ->where('taskHistory.0.status', 'pending')
            ->where('taskHistory.0.reward_coins', 15)
        );
    }

    public function test_tasks_history_page_paginates_all_completed_and_pending_tasks(): void
    {
        $user = User::factory()->create();

        $task = Task::create([
            'title' => 'Sample Task',
            'type' => 'shortlink',
            'reward_coins' => 5,
            'status' => 'active',
        ]);

        // Create 18 user tasks
        for ($i = 1; $i <= 18; $i++) {
            $ut = UserTask::create([
                'user_id' => $user->id,
                'task_id' => $task->id,
                'status' => 'approved',
            ]);
            $ut->created_at = now()->subHours(20 - $i);
            $ut->save();
        }

        $response = $this->actingAs($user)->get('/tasks-history');
        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page
            ->component('Tasks/History')
            ->has('taskHistory.data', 15) // Paginated 15 per page
            ->where('taskHistory.total', 18)
            ->where('taskHistory.last_page', 2)
        );
    }

    public function test_deleted_campaign_fallback_handled_safely(): void
    {
        $user = User::factory()->create();

        $service = CampaignService::create([
            'platform' => 'Website',
            'action' => 'visit',
            'clicker_reward' => 20.00,
            'creator_cost' => 25.00,
            'min_clicks' => 5,
            'max_clicks' => 100,
            'is_active' => true,
        ]);

        $campaign = Campaign::create([
            'user_id' => User::factory()->create()->id,
            'campaign_service_id' => $service->id,
            'title' => 'Campaign to be deleted',
            'target_url' => 'https://example.com',
            'type' => 'Website',
            'action' => 'visit',
            'proof_type' => 'screenshot',
            'budget_points' => 100,
            'cost_per_click' => 20.00,
            'target_clicks' => 5,
            'total_clicks' => 0,
            'status' => 'active',
        ]);

        UserTask::create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'status' => 'approved',
        ]);

        // Delete the parent campaign
        $campaign->delete();

        $response = $this->actingAs($user)->get('/tasks');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Tasks/Index')
            ->has('taskHistory', 1)
        );

        $historyResponse = $this->actingAs($user)->get('/tasks-history');
        $historyResponse->assertStatus(200);
        $historyResponse->assertInertia(fn ($page) => $page
            ->component('Tasks/History')
            ->has('taskHistory.data', 1)
        );
    }
}
