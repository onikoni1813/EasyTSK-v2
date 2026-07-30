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
                    '/dashboard'
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
     * Update daily streak for user (3 tasks per day = 1 streak count)
     */
    public function updateDailyStreak(User $user): void
    {
        $today = Carbon::today();

        $streak = DailyStreak::firstOrCreate(
            ['user_id' => $user->id],
            [
                'streak_count' => 0,
                'tasks_completed_today' => 0,
                'last_completed_date' => null,
            ]
        );

        $lastDate = $streak->last_completed_date ? Carbon::parse($streak->last_completed_date) : null;

        if ($lastDate && $lastDate->isToday()) {
            // Cap at 3 — no need to count beyond the daily goal
            if ($streak->tasks_completed_today < 3) {
                $streak->tasks_completed_today += 1;
                $streak->save();
            } else {
                return; // Already hit today's goal, nothing more to do
            }
        } elseif ($lastDate && $lastDate->isYesterday()) {
            $streak->tasks_completed_today = 1;
            $streak->last_completed_date = $today;
            $streak->save();
        } else {
            if ($lastDate && !$lastDate->isToday()) {
                $streak->streak_count = 0;
            }
            $streak->tasks_completed_today = 1;
            $streak->last_completed_date = $today;
            $streak->save();
        }

        if ($streak->tasks_completed_today === 3) {
            $streak->streak_count += 1;
            $streak->save();
            
            if ($streak->streak_count > 0 && $streak->streak_count % 7 === 0) {
                $user->update(['spin_available_at' => now()]);
            }
        }
    }
}
