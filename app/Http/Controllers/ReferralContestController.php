<?php

namespace App\Http\Controllers;

use App\Models\ReferralContest;
use App\Models\ReferralContestWinner;
use App\Services\ReferralContestService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReferralContestController extends Controller
{
    protected ReferralContestService $contestService;

    public function __construct(ReferralContestService $contestService)
    {
        $this->contestService = $contestService;
    }

    /**
     * Display the Top Referrer Contest Hub page to users
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get currently active contest or latest contest
        $activeContest = ReferralContest::where('status', 'active')
            ->where('end_date', '>=', now())
            ->first();

        $leaderboard = [];
        $currentUserRank = null;

        if ($activeContest) {
            $data = $this->contestService->getLeaderboard($activeContest, $user, false);
            $leaderboard = $data['leaderboard'];
            $currentUserRank = $data['current_user_rank'];
        }

        // Past winners hall of fame
        $pastWinners = ReferralContestWinner::with(['user:id,name', 'contest:id,title,end_date'])
            ->latest('id')
            ->take(30)
            ->get();

        return Inertia::render('Referrals/Contest', [
            'activeContest' => $activeContest,
            'leaderboard' => $leaderboard,
            'currentUserRank' => $currentUserRank,
            'pastWinners' => $pastWinners,
        ]);
    }
}
