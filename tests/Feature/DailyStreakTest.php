<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Models\DailyStreak;
use App\Services\GamificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyStreakTest extends TestCase
{
    use RefreshDatabase;

    protected GamificationService $gamificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gamificationService = app(GamificationService::class);
    }

    public function test_new_user_has_zero_streak()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);

        $streak = $this->gamificationService->getDailyStreak($user);
        $this->assertEquals(0, $streak->streak_count);
        $this->assertEquals(0, $streak->tasks_completed_today);
    }

    public function test_completing_tasks_increments_today_count_and_triggers_streak_on_third_task()
    {
        $user = User::factory()->create();

        // 1st task approved today
        $this->gamificationService->updateDailyStreak($user);
        $streak = $this->gamificationService->getDailyStreak($user);
        $this->assertEquals(0, $streak->streak_count);
        $this->assertEquals(1, $streak->tasks_completed_today);

        // 2nd task approved today
        $this->gamificationService->updateDailyStreak($user);
        $streak = $this->gamificationService->getDailyStreak($user);
        $this->assertEquals(0, $streak->streak_count);
        $this->assertEquals(2, $streak->tasks_completed_today);

        // 3rd task approved today -> Streak goal achieved!
        $this->gamificationService->updateDailyStreak($user);
        $streak = $this->gamificationService->getDailyStreak($user);
        $this->assertEquals(1, $streak->streak_count);
        $this->assertEquals(3, $streak->tasks_completed_today);

        // 4th task approved today -> Streak count remains 1
        $this->gamificationService->updateDailyStreak($user);
        $streak = $this->gamificationService->getDailyStreak($user);
        $this->assertEquals(1, $streak->streak_count);
        $this->assertEquals(4, $streak->tasks_completed_today);
    }

    public function test_streak_persists_on_next_day_if_previous_day_goal_was_met()
    {
        $user = User::factory()->create();

        // Day 1: Complete 3 tasks
        Carbon::setTestNow(Carbon::create(2026, 8, 20, 12, 0, 0));
        for ($i = 0; $i < 3; $i++) {
            $this->gamificationService->updateDailyStreak($user);
        }
        $streak = $this->gamificationService->getDailyStreak($user);
        $this->assertEquals(1, $streak->streak_count);

        // Day 2: Next day arrives
        Carbon::setTestNow(Carbon::create(2026, 8, 21, 10, 0, 0));
        $streakDay2 = $this->gamificationService->getDailyStreak($user);
        $this->assertEquals(1, $streakDay2->streak_count);
        $this->assertEquals(0, $streakDay2->tasks_completed_today);

        // Complete 3 tasks on Day 2
        for ($i = 0; $i < 3; $i++) {
            $this->gamificationService->updateDailyStreak($user);
        }
        $streakDay2End = $this->gamificationService->getDailyStreak($user);
        $this->assertEquals(2, $streakDay2End->streak_count);
        $this->assertEquals(3, $streakDay2End->tasks_completed_today);
    }

    public function test_streak_resets_if_previous_day_goal_was_not_met()
    {
        $user = User::factory()->create();

        // Day 1: Complete 3 tasks
        Carbon::setTestNow(Carbon::create(2026, 8, 20, 12, 0, 0));
        for ($i = 0; $i < 3; $i++) {
            $this->gamificationService->updateDailyStreak($user);
        }

        // Day 2: User completes ONLY 1 task (fails 3-task goal)
        Carbon::setTestNow(Carbon::create(2026, 8, 21, 12, 0, 0));
        $this->gamificationService->updateDailyStreak($user);

        // Day 3: Next day arrives
        Carbon::setTestNow(Carbon::create(2026, 8, 22, 12, 0, 0));
        $streakDay3 = $this->gamificationService->getDailyStreak($user);

        // Streak MUST reset to 0 because Day 2 requirement was not met
        $this->assertEquals(0, $streakDay3->streak_count);
        $this->assertEquals(0, $streakDay3->tasks_completed_today);
    }

    public function test_streak_resets_if_a_day_was_skipped()
    {
        $user = User::factory()->create();

        // Day 1: Complete 3 tasks
        Carbon::setTestNow(Carbon::create(2026, 8, 20, 12, 0, 0));
        for ($i = 0; $i < 3; $i++) {
            $this->gamificationService->updateDailyStreak($user);
        }

        // Day 2: Skipped completely

        // Day 3: User logs in
        Carbon::setTestNow(Carbon::create(2026, 8, 22, 12, 0, 0));
        $streakDay3 = $this->gamificationService->getDailyStreak($user);

        $this->assertEquals(0, $streakDay3->streak_count);
        $this->assertEquals(0, $streakDay3->tasks_completed_today);
    }

    public function test_reaching_7_day_streak_unlocks_bonus_wheel_spin()
    {
        $user = User::factory()->create();

        for ($day = 1; $day <= 7; $day++) {
            Carbon::setTestNow(Carbon::create(2026, 8, $day, 12, 0, 0));
            for ($task = 0; $task < 3; $task++) {
                $this->gamificationService->updateDailyStreak($user);
            }
        }

        $user->refresh();
        $streak = $this->gamificationService->getDailyStreak($user);

        $this->assertEquals(7, $streak->streak_count);
        $this->assertNotNull($user->spin_available_at);
        $this->assertTrue($user->canSpin());
    }
}
