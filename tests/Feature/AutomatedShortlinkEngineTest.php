<?php

namespace Tests\Feature;

use App\Models\ShortlinkSession;
use App\Models\Task;
use App\Models\User;
use App\Models\UserTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AutomatedShortlinkEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_start_shortlink_session(): void
    {
        $user = User::factory()->create(['health' => 100, 'main_balance' => 0]);

        $task = Task::create([
            'title' => 'ShrinkMe 50 Coins',
            'type' => 'shortlink',
            'provider_name' => 'ShrinkMe',
            'target_url' => 'https://shrinkme.io/api',
            'secret_code' => 'test_api_key_123',
            'reward_coins' => 50.00,
            'reward_xp' => 10,
            'cooldown_hours' => 24,
            'status' => 'active',
        ]);

        Http::fake([
            'shrinkme.io/api*' => Http::response([
                'status' => 'success',
                'shortenedUrl' => 'https://shrinkme.io/testLink123',
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson("/tasks/{$task->id}/shortlink/start");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'shortened_url' => 'https://shrinkme.io/testLink123',
        ]);

        $session = ShortlinkSession::where('user_id', $user->id)->where('task_id', $task->id)->first();
        $this->assertNotNull($session);
        $this->assertEquals('pending', $session->status);
    }

    public function test_user_completes_shortlink_and_receives_task_reward(): void
    {
        $user = User::factory()->create(['health' => 100, 'main_balance' => 10.00, 'xp_points' => 5]);

        $task = Task::create([
            'title' => 'Exe.io 75 Coins',
            'type' => 'shortlink',
            'provider_name' => 'Exe.io',
            'target_url' => 'https://exe.io/api',
            'secret_code' => 'test_key',
            'reward_coins' => 75.00,
            'reward_xp' => 15,
            'cooldown_hours' => 24,
            'status' => 'active',
        ]);

        $session = ShortlinkSession::create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'token' => 'unique_token_xyz_12345',
            'status' => 'pending',
            'started_at' => now()->subSeconds(20), // 20s ago
            'expires_at' => now()->addMinutes(20),
        ]);

        $response = $this->actingAs($user)->get("/tasks/shortlink/verify/{$session->token}");

        $response->assertRedirect('/tasks');

        // Check user balance updated
        $user->refresh();
        $this->assertEquals(85.00, (float)$user->main_balance);
        $this->assertGreaterThanOrEqual(20, (int)$user->xp_points);

        // Check session marked completed
        $session->refresh();
        $this->assertEquals('completed', $session->status);
        $this->assertNotNull($session->completed_at);

        // Check UserTask approved
        $userTask = UserTask::where('user_id', $user->id)->where('task_id', $task->id)->first();
        $this->assertNotNull($userTask);
        $this->assertEquals('approved', $userTask->status);
    }
}
