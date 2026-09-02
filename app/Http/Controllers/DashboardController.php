<?php

namespace App\Http\Controllers;

use App\Models\DailyStreak;
use App\Models\ReferralTracking;
use App\Models\Task;
use App\Models\UserTask;
use App\Models\User;
use App\Services\GamificationService;
use App\Services\WelcomeBonusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(WelcomeBonusService $welcomeBonusService, GamificationService $gamificationService)
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

        // Daily Streak calculation: evaluate streak state accurately via GamificationService
        $dailyStreak = $gamificationService->getDailyStreak($user);
        $streakCount = (int) $dailyStreak->streak_count;
        $tasksCompletedToday = (int) $dailyStreak->tasks_completed_today;

        // Task statistics
        $completedTasksCount = UserTask::where('user_id', $user->id)
            ->where('status', 'approved')
            ->distinct('task_id')
            ->count('task_id');

        $pendingTasksCount = UserTask::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $rejectedTasksCount = UserTask::where('user_id', $user->id)
            ->where('status', 'rejected')
            ->count();

        $totalActiveTasks = Task::where('status', 'active')->count();

        // Dynamic level info from Level model
        $levels = \App\Models\Level::orderBy('level_number', 'asc')->get();
        $currentLevel = $levels->where('level_number', $user->level)->first();
        $nextLevel = $levels->where('level_number', '>', $user->level)->first();

        $currentLevelXp = $currentLevel ? (int) $currentLevel->xp_required : 0;
        $nextLevelXp = $nextLevel ? (int) $nextLevel->xp_required : ($currentLevelXp > 0 ? $currentLevelXp + 500 : 500);
        $nextLevelNumber = $nextLevel ? (int) $nextLevel->level_number : ($user->level + 1);

        return Inertia::render('Dashboard', [
            'user' => [
                'id'                        => $user->id,
                'name'                      => $user->name,
                'email'                     => $user->email,
                'phone'                     => $user->phone,
                'role'                      => $user->role,
                'joined_at'                 => $user->created_at ? $user->created_at->format('M Y') : null,
                'main_balance'              => (float) $user->main_balance,
                'pending_balance'           => (float) $user->pending_balance,
                'locked_balance'            => (float) $user->locked_balance,
                'level'                     => (int) $user->level,
                'xp_points'                 => (int) $user->xp_points,
                'current_level_xp'          => $currentLevelXp,
                'next_level_xp'             => $nextLevelXp,
                'next_level_number'         => $nextLevelNumber,
                'has_claimed_welcome_bonus' => (bool) $user->has_claimed_welcome_bonus,
                'welcome_bonus_amount'      => (float) ($user->welcome_bonus_amount ?? 50),
                'referral_code'             => $user->referral_code,
                'health'                    => (int) ($user->health ?? 100),
                'risk_score'                => (float) ($user->risk_score ?? 0),
                'is_banned'                 => (bool) $user->is_banned,
            ],
            'streakCount'           => $streakCount,
            'tasksCompletedToday'   => $tasksCompletedToday,
            'completedTasksCount'   => $completedTasksCount,
            'pendingTasksCount'     => $pendingTasksCount,
            'rejectedTasksCount'    => $rejectedTasksCount,
            'totalActiveTasks'      => $totalActiveTasks,
            'canSpin'               => $user->canSpin(),
            'tutorialVideoUrl'      => \App\Models\AppSetting::getByKey('tutorial_video_url', 'https://www.youtube.com'),
        ]);
    }



    public function referralHistory(Request $request)
    {
        $user = Auth::user();
        
        $referrals = ReferralTracking::where('referrer_id', $user->id)
            ->with(['referredUser:id,name,email,phone,created_at'])
            ->latest()
            ->paginate(5);

        $referredUserIds = $referrals->pluck('referred_user_id')->filter()->unique()->toArray();
        $tasksCompletedCounts = [];
        if (!empty($referredUserIds)) {
            $tasksCompletedCounts = UserTask::whereIn('user_id', $referredUserIds)
                ->where('status', 'approved')
                ->selectRaw('user_id, COUNT(*) as aggregate')
                ->groupBy('user_id')
                ->pluck('aggregate', 'user_id')
                ->toArray();
        }

        $formatted = $referrals->getCollection()->map(function ($r) use ($tasksCompletedCounts) {
            $tasksCompleted = $tasksCompletedCounts[$r->referred_user_id] ?? 0;
            return [
                'id'              => $r->id,
                'referred_user'   => $r->referredUser ? [
                    'id'        => $r->referredUser->id,
                    'name'      => $r->referredUser->name,
                    'email'     => $r->referredUser->email,
                    'phone'     => $r->referredUser->phone,
                    'joined_at' => $r->referredUser->created_at ? $r->referredUser->created_at->format('M d, Y') : null,
                ] : null,
                'tasks_completed' => $tasksCompleted,
                'is_unlocked'     => in_array($r->status, ['unlocked', 'claimed']),
                'locked_reward'   => (float) $r->locked_reward,
                'target_amount'   => (float) ($r->target_amount ?? $r->locked_reward ?: 1),
                'earned_so_far'   => (float) $r->earned_so_far,
                'status'          => $r->status,
                'joined_at'       => $r->created_at ? $r->created_at->format('M d, Y') : '',
            ];
        });

        return response()->json([
            'data'         => $formatted,
            'current_page' => $referrals->currentPage(),
            'last_page'    => $referrals->lastPage(),
            'total'        => $referrals->total(),
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

    public function markNotificationRead($notification)
    {
        $model = $notification instanceof \App\Models\Notification
            ? $notification
            : \App\Models\Notification::find($notification);

        if ($model && (int) $model->user_id === (int) Auth::id()) {
            if (!$model->read_at) {
                $model->update(['read_at' => now()]);
            }
        }

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    public function markAllNotificationsRead()
    {
        \App\Models\Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }
}
