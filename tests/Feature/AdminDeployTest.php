<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDeployTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_run_deploy_commands(): void
    {
        $adminPrefix = env('ADMIN_PANEL_PATH', 'secret-panel');
        $response = $this->postJson("/{$adminPrefix}/deploy/run", [
            'command' => 'cache_clear',
        ]);

        $response->assertStatus(401);
    }

    public function test_non_admin_cannot_run_deploy_commands(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $adminPrefix = env('ADMIN_PANEL_PATH', 'secret-panel');

        $response = $this->actingAs($user)->postJson("/{$adminPrefix}/deploy/run", [
            'command' => 'cache_clear',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_run_cache_clear_without_csrf_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $adminPrefix = env('ADMIN_PANEL_PATH', 'secret-panel');

        // Without passing CSRF token header
        $response = $this->actingAs($admin)->post("/{$adminPrefix}/deploy/run", [
            'command' => 'cache_clear',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'command' => 'php artisan cache:clear',
        ]);
    }
}
