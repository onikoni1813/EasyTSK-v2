<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Models\UserTask;
use Illuminate\Support\Facades\DB;

class WelcomeBonusService
{
    /**
     * Check if the user has completed all active tasks and unlock the welcome bonus if so.
     *
     * @param User $user
     * @return bool True if unlocked now, false otherwise
     */
    public function checkAndUnlock(User $user): bool
    {
        if ($user->has_claimed_welcome_bonus) {
            return false;
        }

        // Count total active tasks in the system
        $totalActiveTasks = Task::where('status', 'active')->count();

        // If there are no tasks, maybe we shouldn't unlock it, or we should?
        // Let's say they must complete at least the currently available tasks.
        if ($totalActiveTasks === 0) {
            return false; // Cannot unlock if there are no tasks to complete
        }

        // Count how many UNIQUE tasks the user has approved
        $completedTasks = UserTask::where('user_id', $user->id)
            ->where('status', 'approved')
            ->distinct('task_id')
            ->count('task_id');

        if ($completedTasks >= $totalActiveTasks) {
            $this->unlockBonus($user);
            return true;
        }

        return false;
    }

    /**
     * Actually unlock the bonus
     */
    private function unlockBonus(User $user): void
    {
        DB::transaction(function () use ($user) {
            /** @var User $freshUser */
            $freshUser = User::where('id', $user->id)->lockForUpdate()->first();

            if ($freshUser && !$freshUser->has_claimed_welcome_bonus) {
                $amount = (float) $freshUser->welcome_bonus_amount;
                $actualTransfer = min($amount, (float) $freshUser->locked_balance);
                
                if ($actualTransfer > 0) {
                    $freshUser->decrement('locked_balance', $actualTransfer);
                    $freshUser->increment('main_balance', $actualTransfer);
                }
                
                // Mark as claimed
                $freshUser->update(['has_claimed_welcome_bonus' => true]);

                \App\Models\Notification::send(
                    $freshUser,
                    'Welcome Bonus Unlocked! 🚀',
                    "Congratulations! You completed your initial tasks and unlocked {$actualTransfer} bonus points to your main balance!",
                    'success',
                    '/dashboard'
                );
                
                // Optionally award XP for the welcome bonus (was +10 XP before)
                $freshUser->addXp(10);
            }
        });
    }
}
