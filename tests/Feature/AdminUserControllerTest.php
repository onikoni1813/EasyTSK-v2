<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserTask;
use App\Models\Task;
use App\Models\Withdrawal;
use App\Models\ReferralTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getAdminUser()
    {
        return User::factory()->create([
            'role' => 'admin',
            'health' => 100,
            'risk_score' => 0,
            'is_banned' => false,
            'phone' => '01700000001',
        ]);
    }

    public function test_admin_can_view_users_index_page()
    {
        $admin = $this->getAdminUser();
        $adminPath = config('app.admin_path', 'secret-panel');

        User::factory()->count(5)->sequence(fn ($sq) => ['phone' => '0170000001' . $sq->index])->create();

        $response = $this->actingAs($admin)->get("/{$adminPath}/users");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Users/Index')->has('users.data'));
    }

    public function test_admin_can_search_users()
    {
        $admin = $this->getAdminUser();
        $adminPath = config('app.admin_path', 'secret-panel');

        $targetUser = User::factory()->create([
            'name' => 'Special Search Target',
            'phone' => '01799999999',
            'email' => 'special@example.com',
        ]);

        User::factory()->count(3)->sequence(fn ($sq) => ['phone' => '0170000002' . $sq->index])->create();

        $response = $this->actingAs($admin)->get("/{$adminPath}/users?search=Special");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.id', $targetUser->id)
        );
    }

    public function test_admin_can_update_user_details()
    {
        $admin = $this->getAdminUser();
        $adminPath = config('app.admin_path', 'secret-panel');

        $targetUser = User::factory()->create([
            'name' => 'Old Name',
            'phone' => '01712345678',
            'email' => 'old@example.com',
            'main_balance' => 10.00,
            'pending_balance' => 5.00,
            'role' => 'user',
            'is_banned' => false,
            'risk_score' => 10,
            'health' => 100,
        ]);

        $response = $this->actingAs($admin)->put("/{$adminPath}/users/{$targetUser->id}", [
            'name'            => 'Updated Name',
            'email'           => 'old@example.com',
            'phone'           => '01712345678',
            'main_balance'    => 150.50,
            'pending_balance' => 20.00,
            'role'            => 'user',
            'is_banned'       => 0,
            'risk_score'      => 25.0,
            'health'          => 80,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id'           => $targetUser->id,
            'name'         => 'Updated Name',
            'main_balance' => 150.50,
            'risk_score'   => 25,
            'health'       => 80,
        ]);
    }

    public function test_admin_can_fetch_user_activity_history()
    {
        $admin = $this->getAdminUser();
        $adminPath = config('app.admin_path', 'secret-panel');

        $user = User::factory()->create([
            'name' => 'Test History User',
            'phone' => '01755555555',
        ]);

        // Create Task directly
        $task = Task::create([
            'title' => 'Test Task',
            'type' => 'website',
            'reward_coins' => 50,
            'category' => 'general',
            'difficulty' => 'easy',
            'is_active' => true,
        ]);

        UserTask::create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'status' => 'approved',
            'submitted_data' => ['proof' => 'sample proof'],
        ]);

        // Create Withdrawal
        Withdrawal::create([
            'user_id' => $user->id,
            'amount_bdt' => 100,
            'amount_coins' => 100,
            'payment_method' => 'bkash',
            'account_details' => '01700000000',
            'status' => 'paid',
            'charge_coins' => 5,
        ]);

        $response = $this->actingAs($admin)->get("/{$adminPath}/users/{$user->id}/history");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'phone', 'email', 'created_at'],
            'stats' => ['total_tasks', 'approved_tasks', 'total_referrals', 'total_withdrawn_bdt', 'total_user_income', 'total_admin_revenue'],
            'tasks',
            'referrals',
            'withdrawals',
        ]);

        $response->assertJson([
            'user' => [
                'id' => $user->id,
                'name' => 'Test History User',
            ],
            'stats' => [
                'approved_tasks' => 1,
            ],
        ]);
    }

    public function test_admin_can_ban_and_unban_user()
    {
        $admin = $this->getAdminUser();
        $adminPath = config('app.admin_path', 'secret-panel');

        $user = User::factory()->create([
            'is_banned' => false,
            'phone' => '01788888888',
        ]);

        $response = $this->actingAs($admin)->post("/{$adminPath}/users/{$user->id}/ban");
        $response->assertRedirect();
        $this->assertTrue((bool)$user->fresh()->is_banned);

        $response2 = $this->actingAs($admin)->post("/{$adminPath}/users/{$user->id}/ban");
        $response2->assertRedirect();
        $this->assertFalse((bool)$user->fresh()->is_banned);
    }
}
