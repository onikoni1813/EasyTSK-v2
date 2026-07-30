<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralContest;
use App\Services\ReferralContestService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Exception;

class AdminReferralContestController extends Controller
{
    protected ReferralContestService $contestService;

    public function __construct(ReferralContestService $contestService)
    {
        $this->contestService = $contestService;
    }

    /**
     * Admin view for managing referral contests and inspecting live leaderboard
     */
    public function index()
    {
        $contests = ReferralContest::with('winners.user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        $activeContest = ReferralContest::where('status', 'active')->first();
        $leaderboardData = [];

        if ($activeContest) {
            $leaderboardData = $this->contestService->getLeaderboard($activeContest, null, true);
        }

        return Inertia::render('Admin/ReferralContests/Index', [
            'contests' => $contests,
            'activeContest' => $activeContest,
            'leaderboard' => $leaderboardData['leaderboard'] ?? [],
        ]);
    }

    /**
     * Create a new referral contest
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'min_unlocked_required' => 'required|integer|min:1',
            'prizes' => 'required|array|min:1',
            'prizes.*.rank' => 'required|integer|min:1',
            'prizes.*.reward' => 'required|numeric|min:0.01',
        ]);

        // Check if there is an active contest
        $existingActive = ReferralContest::where('status', 'active')->exists();
        if ($existingActive) {
            return back()->with('error', 'There is already an active contest. Please complete or cancel it before creating a new one.');
        }

        ReferralContest::create([
            'title' => $validated['title'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'min_unlocked_required' => $validated['min_unlocked_required'],
            'prizes' => $validated['prizes'],
            'status' => 'active',
        ]);

        return back()->with('success', 'Referral contest created successfully!');
    }

    /**
     * Distribute rewards to active contest winners
     */
    public function distribute(ReferralContest $contest)
    {
        try {
            $result = $this->contestService->distributeRewards($contest);

            return back()->with('success', "Rewards distributed successfully to {$result['winners_count']} top referrers!");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel an active contest
     */
    public function cancel(ReferralContest $contest)
    {
        if ($contest->status !== 'active') {
            return back()->with('error', 'Only active contests can be cancelled.');
        }

        $contest->update(['status' => 'cancelled']);

        return back()->with('success', 'Contest cancelled successfully.');
    }
}
