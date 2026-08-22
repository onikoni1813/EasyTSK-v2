<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\ReferralTracking;
use App\Models\User;
use App\Models\UserTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReferralController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // Auto-generate referral code if missing
        if (!$user->referral_code) {
            $user->update(['referral_code' => User::generateReferralCode()]);
            $user->refresh();
        }

        $referralBonus = (float) AppSetting::getByKey('referral_bonus', '500');
        $referralTarget = (float) AppSetting::getByKey('referral_target', '1000');

        $statsQuery = ReferralTracking::where('referrer_id', $user->id)
            ->selectRaw('
                COALESCE(SUM(CASE WHEN status = "locked" THEN locked_reward ELSE 0 END), 0) as locked_points,
                COALESCE(SUM(CASE WHEN status IN ("unlocked", "claimed") THEN locked_reward ELSE 0 END), 0) as unlocked_points,
                COALESCE(SUM(locked_reward), 0) as total_points
            ')->first();

        $stats = [
            'locked_points'   => (float) ($statsQuery->locked_points ?? 0),
            'unlocked_points' => (float) ($statsQuery->unlocked_points ?? 0),
            'total_points'    => (float) ($statsQuery->total_points ?? 0),
        ];

        // Use standard pagination for the full page
        $referrals = ReferralTracking::where('referrer_id', $user->id)
            ->with(['referredUser:id,name,email,phone,created_at'])
            ->latest()
            ->paginate(15);

        // Batch query task completion counts for referred users to avoid N+1 queries
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

        // Replace collection with formatted data while preserving pagination metadata
        $referrals->setCollection($formatted);

        return Inertia::render('Referrals/Index', [
            'referrals'      => $referrals,
            'stats'          => $stats,
            'user'           => [
                'id'              => $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'phone'           => $user->phone,
                'referral_code'   => $user->referral_code,
                'main_balance'    => (float) $user->main_balance,
                'pending_balance' => (float) $user->pending_balance,
                'locked_balance'  => (float) $user->locked_balance,
                'level'           => (int) $user->level,
                'xp_points'       => (int) $user->xp_points,
                'joined_at'       => $user->created_at ? $user->created_at->format('M Y') : null,
            ],
            'referral_bonus'  => $referralBonus,
            'referral_target' => $referralTarget,
        ]);
    }
}

