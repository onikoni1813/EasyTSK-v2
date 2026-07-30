<?php

namespace App\Http\Controllers;

use App\Models\ReferralTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReferralController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $statsQuery = ReferralTracking::where('referrer_id', $user->id)
            ->selectRaw('
                COALESCE(SUM(CASE WHEN status = "locked" THEN locked_reward ELSE 0 END), 0) as locked_points,
                COALESCE(SUM(CASE WHEN status IN ("unlocked", "claimed") THEN locked_reward ELSE 0 END), 0) as unlocked_points,
                COALESCE(SUM(locked_reward), 0) as total_points
            ')->first();

        $stats = [
            'locked_points' => (float) $statsQuery->locked_points,
            'unlocked_points' => (float) $statsQuery->unlocked_points,
            'total_points' => (float) $statsQuery->total_points,
        ];

        // Use standard pagination for the full page
        $referrals = ReferralTracking::where('referrer_id', $user->id)
            ->with(['referredUser:id,name'])
            ->latest()
            ->paginate(15);
            
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

        // Replace collection with formatted data but keep pagination meta
        $referrals->setCollection($formatted);

        return Inertia::render('Referrals/Index', [
            'referrals' => $referrals,
            'stats' => $stats,
            'user' => [
                'referral_code' => $user->referral_code,
            ]
        ]);
    }
}
