<?php

namespace Tests\Feature;

use App\Models\ShortlinkProvider;
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

    public function test_user_can_start_shortlink_session_with_standard_adlinkfly_provider(): void
    {
        $user = User::factory()->create(['health' => 100, 'main_balance' => 0]);

        $task = Task::create([
            'title' => 'ShrinkMe 50 Coins',
            'type' => 'shortlink',
            'provider_name' => 'ShrinkMe.io',
            'target_url' => 'https://shrinkme.io/api',
            'secret_code' => 'd4010139b013fb1e1cf8260ace15c49e985fab5d',
            'reward_coins' => 50.00,
            'reward_xp' => 10,
            'cooldown_hours' => 24,
            'status' => 'active',
        ]);

        Http::fake([
            'shrinkme.io/api*' => Http::response([
                'status' => 'success',
                'shortenedUrl' => 'https://shrinkme.io/xyz123',
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson("/tasks/{$task->id}/shortlink/start");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'shortened_url' => 'https://shrinkme.io/xyz123',
        ]);

        $session = ShortlinkSession::where('user_id', $user->id)->where('task_id', $task->id)->first();
        $this->assertNotNull($session);
        $this->assertEquals('pending', $session->status);
    }

    public function test_user_can_start_shortlink_with_adfocus_provider(): void
    {
        $user = User::factory()->create(['health' => 100, 'main_balance' => 0]);

        $task = Task::create([
            'title' => 'AdFocus Task',
            'type' => 'shortlink',
            'provider_name' => 'AdFoc.us',
            'target_url' => 'http://adfoc.us/api/',
            'secret_code' => 'e172743ea8084e90c2dc17231eb274aa',
            'reward_coins' => 40.00,
            'reward_xp' => 8,
            'cooldown_hours' => 0,
            'status' => 'active',
        ]);

        Http::fake([
            'adfoc.us/api/*' => Http::response('http://adfoc.us/987654', 200),
        ]);

        $response = $this->actingAs($user)->postJson("/tasks/{$task->id}/shortlink/start");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'shortened_url' => 'http://adfoc.us/987654',
        ]);
    }

    public function test_user_can_start_shortlink_with_shrtfly_provider(): void
    {
        $user = User::factory()->create(['health' => 100, 'main_balance' => 0]);

        $task = Task::create([
            'title' => 'ShrtFly Task',
            'type' => 'shortlink',
            'provider_name' => 'ShrtFly.com',
            'target_url' => 'https://shrtfly.com/api',
            'secret_code' => 'a544689d14efd1e28111b5455f24b90a',
            'reward_coins' => 60.00,
            'reward_xp' => 12,
            'cooldown_hours' => 12,
            'status' => 'active',
        ]);

        Http::fake([
            'shrtfly.com/api*' => Http::response([
                'status' => 'success',
                'result' => [
                    'original_url' => 'https://easytsk.test',
                    'shorten_url' => 'https://shrtfly.com/custom123',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson("/tasks/{$task->id}/shortlink/start");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'shortened_url' => 'https://shrtfly.com/custom123',
        ]);
    }

    public function test_provider_credentials_fallback_to_saved_shortlink_provider_table(): void
    {
        $user = User::factory()->create(['health' => 100, 'main_balance' => 0]);

        // Create saved provider in table
        ShortlinkProvider::create([
            'name' => 'GPLinks',
            'slug' => 'gplinks',
            'api_url' => 'https://api.gplinks.com/api',
            'api_key' => '6411a5a0ce3f100e690125a539e2d33217ec2fe4',
            'icon' => '💎',
            'daily_limit' => 1,
            'is_active' => true,
        ]);

        // Task only specifies provider_name
        $task = Task::create([
            'title' => 'GPLinks Auto Task',
            'type' => 'shortlink',
            'provider_name' => 'GPLinks',
            'target_url' => '',
            'secret_code' => '',
            'reward_coins' => 30.00,
            'reward_xp' => 5,
            'cooldown_hours' => 24,
            'status' => 'active',
        ]);

        Http::fake([
            'api.gplinks.com/api*' => Http::response([
                'status' => 'success',
                'shortenedUrl' => 'https://gplinks.co/quickEarn',
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson("/tasks/{$task->id}/shortlink/start");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'shortened_url' => 'https://gplinks.co/quickEarn',
        ]);
    }

    public function test_user_completes_shortlink_and_receives_task_reward(): void
    {
        $user = User::factory()->create(['health' => 100, 'main_balance' => 10.00, 'xp_points' => 5]);

        $task = Task::create([
            'title' => 'Exe.io 75 Coins',
            'type' => 'shortlink',
            'provider_name' => 'Exe.io',
            'target_url' => 'https://exe.io/api',
            'secret_code' => '7f51aa2a6c67065832e859fbeeb93415a7e83112',
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
