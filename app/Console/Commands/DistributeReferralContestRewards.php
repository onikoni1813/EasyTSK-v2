<?php

namespace App\Console\Commands;

use App\Models\ReferralContest;
use App\Services\ReferralContestService;
use Illuminate\Console\Command;
use Exception;

class DistributeReferralContestRewards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'referral-contest:distribute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically check and distribute rewards for expired referral contests';

    /**
     * Execute the console command.
     */
    public function handle(ReferralContestService $contestService): int
    {
        $expiredActiveContests = ReferralContest::where('status', 'active')
            ->where('end_date', '<=', now())
            ->whereNull('distributed_at')
            ->get();

        if ($expiredActiveContests->isEmpty()) {
            $this->info('No expired active referral contests found for automatic reward distribution.');
            return self::SUCCESS;
        }

        foreach ($expiredActiveContests as $contest) {
            $this->info("Processing contest ID #{$contest->id}: '{$contest->title}'...");

            try {
                $result = $contestService->distributeRewards($contest);
                $this->info("Successfully distributed rewards for contest #{$contest->id} to {$result['winners_count']} top referrers!");
            } catch (Exception $e) {
                $this->error("Failed to distribute rewards for contest #{$contest->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
