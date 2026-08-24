<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserLevelController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $levels = Level::orderBy('level_number', 'asc')->get();

        $currentLevelObj = $levels->where('level_number', $user->level)->first();
        $nextLevelObj = $levels->where('level_number', '>', $user->level)->first();

        $currentLevelXp = $currentLevelObj ? (int) $currentLevelObj->xp_required : 0;
        $nextLevelXp = $nextLevelObj ? (int) $nextLevelObj->xp_required : ($currentLevelXp > 0 ? $currentLevelXp + 500 : 500);
        $nextLevelNumber = $nextLevelObj ? (int) $nextLevelObj->level_number : ($user->level + 1);
        $nextBonusReward = $nextLevelObj ? (float) $nextLevelObj->bonus_reward : 0.0;

        // Calculate progress percentage
        $xpRange = max(1, $nextLevelXp - $currentLevelXp);
        $userXpInRange = max(0, $user->xp_points - $currentLevelXp);
        $progressPct = min(100, round(($userXpInRange / $xpRange) * 100, 1));

        $remainingXp = max(0, $nextLevelXp - $user->xp_points);

        // Map tiers for display
        $formattedLevels = $levels->map(function ($l) use ($user, $nextLevelNumber) {
            $isUnlocked = $user->level >= $l->level_number;
            $isCurrent = $user->level === $l->level_number;
            $isNext = $l->level_number === $nextLevelNumber;

            return [
                'id'           => $l->id,
                'level_number' => (int) $l->level_number,
                'xp_required'  => (int) $l->xp_required,
                'bonus_reward' => (float) $l->bonus_reward,
                'is_unlocked'  => $isUnlocked,
                'is_current'   => $isCurrent,
                'is_next'      => $isNext,
            ];
        });

        return Inertia::render('Levels/Index', [
            'user' => [
                'id'                => $user->id,
                'name'              => $user->name,
                'level'             => (int) $user->level,
                'xp_points'         => (int) $user->xp_points,
                'current_level_xp'  => $currentLevelXp,
                'next_level_xp'     => $nextLevelXp,
                'next_level_number' => $nextLevelNumber,
                'remaining_xp'      => (int) $remainingXp,
                'progress_pct'      => (float) $progressPct,
                'next_bonus_reward' => (float) $nextBonusReward,
            ],
            'levels' => $formattedLevels,
        ]);
    }
}
