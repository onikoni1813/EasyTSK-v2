<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLevelPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_levels_page()
    {
        $response = $this->get('/levels');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_levels_page_with_progression_data()
    {
        Level::create(['level_number' => 1, 'xp_required' => 0, 'bonus_reward' => 0]);
        Level::create(['level_number' => 2, 'xp_required' => 100, 'bonus_reward' => 10]);
        Level::create(['level_number' => 3, 'xp_required' => 250, 'bonus_reward' => 25]);

        $user = User::factory()->create([
            'level' => 1,
            'xp_points' => 50,
            'main_balance' => 100,
        ]);

        $response = $this->actingAs($user)->get('/levels');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Levels/Index')
            ->has('user')
            ->where('user.level', 1)
            ->where('user.xp_points', 50)
            ->where('user.next_level_number', 2)
            ->where('user.remaining_xp', 50)
            ->where('user.progress_pct', 50)
            ->has('levels', 3)
        );
    }
}
