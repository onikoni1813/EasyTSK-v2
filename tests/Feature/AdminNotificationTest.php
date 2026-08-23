<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;
    protected string $adminPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@easytsk.com',
            'level' => 1,
        ]);

        $this->regularUser = User::factory()->create([
            'role' => 'user',
            'email' => 'user@easytsk.com',
            'level' => 2,
        ]);

        $this->adminPath = '/' . config('app.admin_path', 'secret-panel');
    }

    public function test_guest_cannot_access_admin_notifications()
    {
        $response = $this->get("{$this->adminPath}/notifications");
        $response->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin_notifications()
    {
        $response = $this->actingAs($this->regularUser)->get("{$this->adminPath}/notifications");
        $response->assertStatus(403);
    }

    public function test_admin_can_view_notifications_center()
    {
        $response = $this->actingAs($this->admin)->get("{$this->adminPath}/notifications");
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Notifications/Index')
            ->has('notifications')
            ->has('levels')
            ->has('totalUsers')
        );
    }

    public function test_admin_can_send_broadcast_to_all_users()
    {
        $response = $this->actingAs($this->admin)->post("{$this->adminPath}/notifications/send", [
            'target_type' => 'all',
            'delivery_mode' => 'drawer',
            'title' => 'Big Update 🎉',
            'message' => 'We launched a new feature!',
            'type' => 'success',
            'action_url' => '/dashboard',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Both admin and regular user should have received notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->regularUser->id,
            'title' => 'Big Update 🎉',
            'message' => 'We launched a new feature!',
            'type' => 'success',
            'is_popup' => 0,
            'action_url' => '/dashboard',
        ]);
    }

    public function test_admin_can_send_popup_notification_to_specific_level()
    {
        $level2User = User::factory()->create(['level' => 2]);
        $level3User = User::factory()->create(['level' => 3]);

        $response = $this->actingAs($this->admin)->post("{$this->adminPath}/notifications/send", [
            'target_type' => 'level',
            'target_level' => 2,
            'delivery_mode' => 'popup',
            'title' => 'Level 2 Perk 🚀',
            'message' => 'Exclusive reward for Level 2!',
            'type' => 'info',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $level2User->id,
            'title' => 'Level 2 Perk 🚀',
            'is_popup' => 1,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $level3User->id,
            'title' => 'Level 2 Perk 🚀',
        ]);
    }

    public function test_admin_can_send_notification_to_single_user_by_email()
    {
        $response = $this->actingAs($this->admin)->post("{$this->adminPath}/notifications/send", [
            'target_type' => 'user',
            'user_query' => 'user@easytsk.com',
            'delivery_mode' => 'drawer',
            'title' => 'Personal Message 📩',
            'message' => 'Important update regarding your account.',
            'type' => 'warning',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->regularUser->id,
            'title' => 'Personal Message 📩',
            'type' => 'warning',
        ]);
    }

    public function test_admin_can_delete_notification()
    {
        $notification = Notification::create([
            'user_id' => $this->regularUser->id,
            'title' => 'To be deleted',
            'message' => 'Goodbye',
            'type' => 'info',
        ]);

        $response = $this->actingAs($this->admin)->delete("{$this->adminPath}/notifications/{$notification->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }
}
