<?php

namespace App\Http\Controllers;

use App\Models\DailyStreak;
use App\Models\ReferralTracking;
use App\Models\Task;
use App\Models\UserTask;
use App\Models\User;
use App\Services\WelcomeBonusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(WelcomeBonusService $welcomeBonusService)
    {
        /** @var User $user */
        $user = Auth::user();

        // Self-healing check: In case total active tasks decreased, they might have met the condition
        // without triggering a new task completion. This ensures they get their bonus.
        if (!$user->has_claimed_welcome_bonus) {
            if ($welcomeBonusService->checkAndUnlock($user)) {
                $user->refresh(); // Reload user state if bonus was just unlocked
            }
        }

        // Auto-generate referral code if missing
        if (!$user->referral_code) {
            $user->update(['referral_code' => User::generateReferralCode()]);
            $user->refresh();
        }

        $dailyStreak       = DailyStreak::where('user_id', $user->id)->first();
        $completedTasksCount = UserTask::where('user_id', $user->id)
            ->where('status', 'approved')
            ->distinct('task_id')
            ->count('task_id');
        $totalActiveTasks  = Task::where('status', 'active')->count();

        // Referral history is now loaded lazily via API

        return Inertia::render('Dashboard', [
            'user' => [
                'id'                        => $user->id,
                'name'                      => $user->name,
                'email'                     => $user->email,
                'main_balance'              => (float) $user->main_balance,
                'pending_balance'           => (float) $user->pending_balance,
                'locked_balance'            => (float) $user->locked_balance,
                'level'                     => $user->level,
                'xp_points'                 => $user->xp_points,
                'has_claimed_welcome_bonus' => $user->has_claimed_welcome_bonus,
                'welcome_bonus_amount'      => (float) ($user->welcome_bonus_amount ?? 50),
                'referral_code'             => $user->referral_code,
                'health'                    => (int) $user->health,
                'risk_score'                => (float) $user->risk_score,
                'is_banned'                 => (bool) $user->is_banned,
            ],
            'streakCount'         => $dailyStreak ? $dailyStreak->streak_count : 0,
            'tasksCompletedToday' => $dailyStreak ? $dailyStreak->tasks_completed_today : 0,
            'completedTasksCount' => $completedTasksCount,
            'totalActiveTasks'    => $totalActiveTasks,

            'canSpin'             => $user->canSpin(),
        ]);
    }



    public function referralHistory(Request $request)
    {
        $user = Auth::user();
        
        $referrals = ReferralTracking::where('referrer_id', $user->id)
            ->with(['referredUser:id,name'])
            ->latest()
            ->paginate(5);

        $formatted = $referrals->getCollection()->map(fn($r) => [
            'id'            => $r->id,
            'referred_user' => $r->referredUser ? ['name' => $r->referredUser->name] : null,
            'tasks_completed' => $r->tasks_completed ?? 0,
            'is_unlocked'   => $r->is_unlocked ?? false,
            'locked_reward' => (float) $r->locked_reward,
            'target_amount' => (float) ($r->target_amount ?? $r->locked_reward ?: 1),
            'earned_so_far' => (float) $r->earned_so_far,
            'status'        => $r->status,
            'joined_at'     => $r->created_at->format('M d, Y'),
        ]);

        return response()->json([
            'data' => $formatted,
            'current_page' => $referrals->currentPage(),
            'last_page' => $referrals->lastPage(),
            'total' => $referrals->total(),
        ]);
    }

    public function notifications()
    {
        $user = Auth::user();
        $notifications = \App\Models\Notification::where('user_id', $user->id)
            ->latest()
            ->take(15)
            ->get();

        $unreadCount = \App\Models\Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    public function markNotificationRead(\App\Models\Notification $notification)
    {
        if ($notification->user_id === Auth::id() && !$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function markAllNotificationsRead()
    {
        \App\Models\Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
