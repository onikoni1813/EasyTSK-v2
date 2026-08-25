<?php

namespace App\Services;

use App\Models\DailyStreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GamificationService
{
    protected WelcomeBonusService $welcomeBonusService;

    public function __construct(WelcomeBonusService $welcomeBonusService)
    {
        $this->welcomeBonusService = $welcomeBonusService;
    }

    /**
     * Award XP to user and calculate level ups
     */
    public function awardXp(User $user, int $xpAmount, bool $updateStreak = true): void
    {
        DB::transaction(function () use ($user, $xpAmount, $updateStreak) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            
            $lockedUser->increment('xp_points', $xpAmount);
            $lockedUser->refresh();

            // Calculate Level Dynamically from DB
            $levels = \App\Models\Level::orderBy('level_number', 'asc')->get();
            $newLevelNumber = 1;
            
            foreach ($levels as $l) {
                if ($lockedUser->xp_points >= $l->xp_required) {
                    $newLevelNumber = $l->level_number;
                }
            }

            if ($newLevelNumber > $lockedUser->level) {
                // Find all levels unlocked between old and new level to award total bonuses
                $unlockedLevels = $levels->where('level_number', '>', $lockedUser->level)
                                         ->where('level_number', '<=', $newLevelNumber);
                
                $totalBonus = $unlockedLevels->sum('bonus_reward');
                
                $lockedUser->update(['level' => $newLevelNumber]);
                
                if ($totalBonus > 0) {
                    $lockedUser->increment('main_balance', $totalBonus);
                }

                \App\Models\Notification::send(
                    $lockedUser,
                    'Level Upgraded! ⚡',
                    "Congratulations! You reached Level {$newLevelNumber}" . ($totalBonus > 0 ? " and earned +{$totalBonus} bonus points!" : "!"),
                    'success',
                    '/dashboard',
                    true
                );
            }

            if ($updateStreak) {
                $this->updateDailyStreak($lockedUser);
            }
            
            // Check if welcome bonus can be unlocked
            $this->welcomeBonusService->checkAndUnlock($lockedUser);
        });
    }

    /**
     * Retrieve or evaluate the user's daily streak, ensuring date transitions and streak resets are calculated accurately.
     */
    public function getDailyStreak(User $user): DailyStreak
    {
        $today = Carbon::today();

        $streak = DailyStreak::where('user_id', $user->id)->lockForUpdate()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'streak_count'          => 0,
                'tasks_completed_today' => 0,
                'last_completed_date'   => null,
            ]
        );

        $lastDate = $streak->last_completed_date ? Carbon::parse($streak->last_completed_date) : null;

        if (!$lastDate) {
            // No previous streak activity at all
            if ($streak->streak_count !== 0 || $streak->tasks_completed_today !== 0) {
                $streak->update(['streak_count' => 0, 'tasks_completed_today' => 0]);
            }
            return $streak;
        }

        if ($lastDate->isToday()) {
            // Data is for today, return as-is
            return $streak;
        }

        if ($lastDate->isYesterday()) {
            // Activity was yesterday
            if ($streak->tasks_completed_today >= 3) {
                // Yesterday's daily streak requirement (3 tasks) WAS met!
                // Streak remains intact. Reset today's task count to 0 for the new day.
                $streak->update([
                    'tasks_completed_today' => 0,
                    'last_completed_date'   => $today,
                ]);
            } else {
                // Yesterday's requirement was NOT met (user completed < 3 tasks yesterday).
                // Streak is broken! Reset streak_count and today's task count.
                $streak->update([
                    'streak_count'          => 0,
                    'tasks_completed_today' => 0,
                    'last_completed_date'   => $today,
                ]);
            }
            return $streak;
        }

        // Activity was 2 or more days ago -> Streak is broken!
        $streak->update([
            'streak_count'          => 0,
            'tasks_completed_today' => 0,
            'last_completed_date'   => $today,
        ]);

        return $streak;
    }

    /**
     * Update daily streak for user (3 tasks per day = 1 streak count)
     */
    public function updateDailyStreak(User $user): void
    {
        // Get fresh, validated streak record for today
        $streak = $this->getDailyStreak($user);

        $oldCount = (int) $streak->tasks_completed_today;
        $newCount = $oldCount + 1;

        $streak->tasks_completed_today = $newCount;
        $streak->last_completed_date   = Carbon::today();
        $streak->save();

        // Check if the user reached the 3-task threshold today (only increment streak once when transitioning from <3 to >=3)
        if ($oldCount < 3 && $newCount >= 3) {
            $streak->increment('streak_count');
            $streak->refresh();

            // Notify user about streak milestone
            \App\Models\Notification::send(
                $user,
                'Daily Streak Extended! 🔥',
                "Awesome! You completed today's daily streak goal. Current streak: {$streak->streak_count} days!",
                'success',
                '/dashboard'
            );

            // Award Spin Wheel opportunity on every 7-day milestone
            if ($streak->streak_count > 0 && $streak->streak_count % 7 === 0) {
                $user->update(['spin_available_at' => now()]);

                \App\Models\Notification::send(
                    $user,
                    'Reward Spin Unlocked! 🎰',
                    "Congratulations on your 7-day streak! You unlocked a free spin on the Daily Bonus Wheel!",
                    'success',
                    '/dashboard',
                    true
                );
            }
        }
    }
}
