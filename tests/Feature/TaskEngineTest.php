<?php

namespace Tests\Feature;

use App\Models\Offerwall;
use App\Models\Task;
use App\Models\User;
use App\Models\UserTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_tasks_page(): void
    {
        $response = $this->get('/tasks');
        $response->assertRedirect('/login');
    }

    public function test_user_can_access_tasks_page_with_inertia_props(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['health' => 100]);

        Task::create([
            'title' => 'Test Shortlink Task',
            'description' => 'Complete shortlink to earn reward',
            'type' => 'shortlink',
            'reward_coins' => 50,
            'reward_xp' => 10,
            'status' => 'active',
        ]);

        Offerwall::create([
            'name' => 'CPAGrip',
            'iframe_url_pattern' => 'https://example.com/offers?subid={user_id}',
            'reward_ratio' => 1.0,
            'status' => true,
            'order' => 1,
        ]);

        $response = $this->actingAs($user)->get('/tasks');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Tasks/Index')
            ->has('tasks', 1)
            ->has('offerwalls', 1)
            ->has('is_locked')
            ->has('pending_tasks_count')
            ->has('health_gate_active')
            ->has('taskHistory')
            ->has('offerwallLogs')
            ->has('offerwallStats')
        );
    }

    public function test_user_can_view_task_history(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $task = Task::create([
            'title' => 'Sample Social Task',
            'type' => 'social',
            'reward_coins' => 30,
            'reward_xp' => 5,
            'status' => 'active',
        ]);

        UserTask::create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'status' => 'approved',
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($user)->get('/tasks-history');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Tasks/History')
            ->has('taskHistory.data', 1)
        );
    }

    public function test_user_can_submit_secret_code_task_with_correct_code(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['main_balance' => 0, 'health' => 90]);

        $task = Task::create([
            'title' => 'Secret Code Task',
            'type' => 'secret_code',
            'secret_code' => 'SUPER123',
            'reward_coins' => 100,
            'reward_xp' => 20,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post("/tasks/{$task->id}/social-proof", [
            'secret_codes' => ['SUPER123'],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user_tasks', [
            'user_id' => $user->id,
            'task_id' => $task->id,
            'status' => 'approved',
        ]);

        $this->assertEquals(100, $user->fresh()->main_balance);
        $this->assertEquals(91, $user->fresh()->health);
    }

    public function test_user_deducted_health_on_incorrect_secret_code(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['health' => 100]);

        $task = Task::create([
            'title' => 'Secret Code Task',
            'type' => 'secret_code',
            'secret_code' => 'RIGHTCODE',
            'reward_coins' => 50,
            'reward_xp' => 10,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post("/tasks/{$task->id}/social-proof", [
            'secret_codes' => ['WRONGCODE'],
        ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(90, $user->fresh()->health);
    }
}
