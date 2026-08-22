<?php

namespace App\Services;

use App\Models\ReferralContest;
use App\Models\ReferralContestWinner;
use App\Models\ReferralTracking;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class ReferralContestService
{
    /**
     * Fetch filtered, security-verified leaderboard for a contest
     */
    public function getLeaderboard(ReferralContest $contest, ?User $currentUser = null, bool $isAdminView = false): array
    {
        // Fetch referral tracking counts for unlocked referrals within contest timeframe
        $rawRankings = DB::table('referral_trackings')
            ->join('users', 'users.id', '=', 'referral_trackings.referrer_id')
            ->select(
                'referral_trackings.referrer_id',
                DB::raw('COUNT(referral_trackings.id) as unlocked_count'),
                DB::raw('MIN(referral_trackings.updated_at) as earliest_unlock')
            )
            ->where('referral_trackings.status', 'unlocked')
            ->whereBetween('referral_trackings.updated_at', [$contest->start_date, $contest->end_date])
            ->where('users.is_banned', false)
            ->where('users.risk_score', '<', 80.00)
            ->groupBy('referral_trackings.referrer_id')
            ->having('unlocked_count', '>=', $contest->min_unlocked_required)
            ->orderBy('unlocked_count', 'DESC')
            ->orderBy('earliest_unlock', 'ASC')
            ->get();

        $prizes = $contest->prizes ?? [];
        $prizeMap = [];
        foreach ($prizes as $p) {
            $prizeMap[(int)$p['rank']] = (float)$p['reward'];
        }

        $leaderboard = [];
        $currentUserRankData = null;
        $rank = 1;

        foreach ($rawRankings as $row) {
            $user = User::find($row->referrer_id);
            if (!$user) {
                continue;
            }

            $estimatedPrize = $prizeMap[$rank] ?? 0.0;

            $item = [
                'rank' => $rank,
                'user_id' => $user->id,
                'name' => $user->name,
                'unlocked_count' => (int)$row->unlocked_count,
                'estimated_prize' => $estimatedPrize,
            ];

            if ($isAdminView) {
                // Fraud analysis for admin view
                $sameDeviceCount = 0;
                if (!empty($user->device_hash)) {
                    $sameDeviceCount = DB::table('referral_trackings')
                        ->join('users as referred', 'referred.id', '=', 'referral_trackings.referred_user_id')
                        ->where('referral_trackings.referrer_id', $user->id)
                        ->whereNotNull('referred.device_hash')
                        ->where('referred.device_hash', '!=', '')
                        ->where('referred.device_hash', $user->device_hash)
                        ->count();
                }

                $item['email'] = $user->email;
                $item['device_hash'] = $user->device_hash;
                $item['risk_score'] = (float)$user->risk_score;
                $item['suspicious_device_count'] = $sameDeviceCount;
            }

            $leaderboard[] = $item;

            if ($currentUser && $user->id === $currentUser->id) {
                $currentUserRankData = [
                    'rank' => $rank,
                    'unlocked_count' => (int)$row->unlocked_count,
                    'estimated_prize' => $estimatedPrize,
                ];
            }

            $rank++;
        }

        // If current user is not in top rankings yet, compute their actual unlocked count
        if ($currentUser && !$currentUserRankData) {
            $userUnlockedCount = ReferralTracking::where('referrer_id', $currentUser->id)
                ->where('status', 'unlocked')
                ->whereBetween('updated_at', [$contest->start_date, $contest->end_date])
                ->count();

            $currentUserRankData = [
                'rank' => null, // Unranked
                'unlocked_count' => $userUnlockedCount,
                'estimated_prize' => 0.0,
            ];
        }

        return [
            'leaderboard' => array_slice($leaderboard, 0, 50), // Return top 50
            'current_user_rank' => $currentUserRankData,
        ];
    }

    /**
     * Atomically distribute rewards to top referrers
     */
    public function distributeRewards(ReferralContest $contest): array
    {
        return DB::transaction(function () use ($contest) {
            /** @var ReferralContest $lockedContest */
            $lockedContest = ReferralContest::where('id', $contest->id)->lockForUpdate()->first();

            if (!$lockedContest) {
                throw new Exception("Contest not found.");
            }

            if ($lockedContest->status !== 'active') {
                throw new Exception("Contest status is '{$lockedContest->status}'. Only active contests can be rewarded.");
            }

            if ($lockedContest->distributed_at !== null) {
                throw new Exception("Rewards for this contest have already been distributed.");
            }

            $leaderboardData = $this->getLeaderboard($lockedContest, null, false);
            $leaderboard = $leaderboardData['leaderboard'];

            $prizes = $lockedContest->prizes ?? [];
            $prizeMap = [];
            foreach ($prizes as $p) {
                $prizeMap[(int)$p['rank']] = (float)$p['reward'];
            }

            $winnersCreated = [];

            foreach ($leaderboard as $entry) {
                $rank = (int)$entry['rank'];
                if (!isset($prizeMap[$rank]) || $prizeMap[$rank] <= 0) {
                    continue; // No prize for this rank
                }

                $rewardAmount = $prizeMap[$rank];
                $userId = (int)$entry['user_id'];
                $unlockedCount = (int)$entry['unlocked_count'];

                // Pessimistic lock on winner user
                $user = User::where('id', $userId)->lockForUpdate()->first();
                if (!$user || $user->is_banned) {
                    continue; // Skip banned/deleted users
                }

                // Log financial transaction before balance increment
                Transaction::log(
                    $user,
                    'credit',
                    $rewardAmount,
                    "🏆 Top Referrer Contest Bonus - {$lockedContest->title} (Rank #{$rank})",
                    'referral_contest_bonus',
                    (string)$lockedContest->id
                );

                // Credit main_balance
                $user->increment('main_balance', $rewardAmount);

                // Record winner row
                $winner = ReferralContestWinner::create([
                    'contest_id' => $lockedContest->id,
                    'user_id' => $user->id,
                    'rank' => $rank,
                    'unlocked_count' => $unlockedCount,
                    'reward_amount' => $rewardAmount,
                ]);

                \App\Models\Notification::send(
                    $user,
                    'Contest Champion! 🏆',
                    "Congratulations! You secured Rank #{$rank} in the Top Referrer Contest '{$lockedContest->title}' and won {$rewardAmount} bonus points!",
                    'success',
                    '/referral-contest'
                );

                $winnersCreated[] = [
                    'user_name' => $user->name,
                    'rank' => $rank,
                    'reward_amount' => $rewardAmount,
                ];
            }

            // Mark contest as completed & set distributed_at timestamp
            $lockedContest->update([
                'status' => 'completed',
                'distributed_at' => now(),
            ]);

            return [
                'contest_id' => $lockedContest->id,
                'winners_count' => count($winnersCreated),
                'winners' => $winnersCreated,
            ];
        });
    }
}
