<?php

namespace Tests\Feature;

use App\Models\ReferralContest;
use App\Models\ReferralContestWinner;
use App\Models\ReferralTracking;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReferralContestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReferralContestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_referral_contest_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/referral-contest');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Referrals/Contest')
            ->has('activeContest')
            ->has('leaderboard')
            ->has('currentUserRank')
            ->has('pastWinners')
        );
    }

    public function test_leaderboard_calculates_unlocked_referrals_and_filters_banned_users(): void
    {
        $contest = ReferralContest::create([
            'title' => 'Weekly Referrer Sprint',
            'start_date' => now()->subDays(2),
            'end_date' => now()->addDays(5),
            'min_unlocked_required' => 1,
            'prizes' => [
                ['rank' => 1, 'reward' => 1000],
                ['rank' => 2, 'reward' => 500],
            ],
            'status' => 'active',
        ]);

        $referrer1 = User::factory()->create(['name' => 'Top Referrer', 'main_balance' => 200]);
        $referrer2 = User::factory()->create(['name' => 'Banned Referrer', 'is_banned' => true]);

        $referred1 = User::factory()->create(['ref_by' => $referrer1->id]);
        $referred2 = User::factory()->create(['ref_by' => $referrer1->id]);
        $referred3 = User::factory()->create(['ref_by' => $referrer2->id]);

        $t1 = ReferralTracking::create([
            'referrer_id' => $referrer1->id,
            'referred_user_id' => $referred1->id,
            'status' => 'unlocked',
        ]);

        $t2 = ReferralTracking::create([
            'referrer_id' => $referrer1->id,
            'referred_user_id' => $referred2->id,
            'status' => 'unlocked',
        ]);

        $t3 = ReferralTracking::create([
            'referrer_id' => $referrer2->id,
            'referred_user_id' => $referred3->id,
            'status' => 'unlocked',
        ]);

        DB::table('referral_trackings')->where('id', $t1->id)->update(['updated_at' => now()->subDay()]);
        DB::table('referral_trackings')->where('id', $t2->id)->update(['updated_at' => now()->subHours(12)]);
        DB::table('referral_trackings')->where('id', $t3->id)->update(['updated_at' => now()->subHours(10)]);

        $service = app(ReferralContestService::class);
        $result = $service->getLeaderboard($contest, $referrer1, false);

        $leaderboard = $result['leaderboard'];
        $userRank = $result['current_user_rank'];

        $this->assertCount(1, $leaderboard);
        $this->assertEquals('Top Referrer', $leaderboard[0]['name']);
        $this->assertEquals(2, $leaderboard[0]['unlocked_count']);
        $this->assertEquals(1000.0, $leaderboard[0]['estimated_prize']);

        $this->assertNotNull($userRank);
        $this->assertEquals(1, $userRank['rank']);
        $this->assertEquals(2, $userRank['unlocked_count']);
    }

    public function test_reward_distribution_credits_balance_and_logs_accurate_transaction(): void
    {
        $contest = ReferralContest::create([
            'title' => 'Monthly Grand Contest',
            'start_date' => now()->subDays(7),
            'end_date' => now()->subMinute(),
            'min_unlocked_required' => 1,
            'prizes' => [
                ['rank' => 1, 'reward' => 1500],
            ],
            'status' => 'active',
        ]);

        $winner = User::factory()->create(['main_balance' => 300.00]);
        $referred = User::factory()->create(['ref_by' => $winner->id]);

        $t = ReferralTracking::create([
            'referrer_id' => $winner->id,
            'referred_user_id' => $referred->id,
            'status' => 'unlocked',
        ]);
        DB::table('referral_trackings')->where('id', $t->id)->update(['updated_at' => now()->subDays(3)]);

        $service = app(ReferralContestService::class);
        $result = $service->distributeRewards($contest);

        $this->assertEquals(1, $result['winners_count']);

        $winner->refresh();
        $this->assertEquals(1800.00, (float)$winner->main_balance);

        $contest->refresh();
        $this->assertEquals('completed', $contest->status);
        $this->assertNotNull($contest->distributed_at);

        $this->assertDatabaseHas('referral_contest_winners', [
            'contest_id' => $contest->id,
            'user_id' => $winner->id,
            'rank' => 1,
            'reward_amount' => 1500.00,
        ]);

        $transaction = Transaction::where('user_id', $winner->id)
            ->where('reference_type', 'referral_contest_bonus')
            ->first();

        $this->assertNotNull($transaction);
        $this->assertEquals(1500.00, (float)$transaction->amount);
        $this->assertEquals(300.00, (float)$transaction->balance_before);
        $this->assertEquals(1800.00, (float)$transaction->balance_after);
    }

    public function test_distribute_cron_command_executes_successfully(): void
    {
        $contest = ReferralContest::create([
            'title' => 'Expired Cron Contest',
            'start_date' => now()->subDays(10),
            'end_date' => now()->subHours(2),
            'min_unlocked_required' => 1,
            'prizes' => [
                ['rank' => 1, 'reward' => 2000],
            ],
            'status' => 'active',
        ]);

        $winner = User::factory()->create(['main_balance' => 50.00]);
        $referred = User::factory()->create(['ref_by' => $winner->id]);

        $t = ReferralTracking::create([
            'referrer_id' => $winner->id,
            'referred_user_id' => $referred->id,
            'status' => 'unlocked',
        ]);
        DB::table('referral_trackings')->where('id', $t->id)->update(['updated_at' => now()->subDays(5)]);

        $this->artisan('referral-contest:distribute')
            ->assertExitCode(0);

        $winner->refresh();
        $this->assertEquals(2050.00, (float)$winner->main_balance);

        $contest->refresh();
        $this->assertEquals('completed', $contest->status);
    }
}
