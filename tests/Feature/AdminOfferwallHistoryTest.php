<?php

namespace Tests\Feature;

use App\Models\Offerwall;
use App\Models\OfferwallLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOfferwallHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'main_balance' => 0,
            'pending_balance' => 0,
        ]);

        $this->user = User::factory()->create([
            'role' => 'user',
            'main_balance' => 100,
            'pending_balance' => 500,
        ]);
    }

    public function test_admin_can_view_offerwalls_with_history_and_stats(): void
    {
        OfferwallLog::create([
            'user_id' => $this->user->id,
            'provider' => 'BitLabs',
            'transaction_id' => 'TX_123',
            'amount' => 500,
            'status' => 'pending',
            'release_time' => now()->addHours(24),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.offerwalls.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Offerwalls/Index')
            ->has('offerwalls')
            ->has('logs.data', 1)
            ->where('logStats.pending_count', 1)
            ->where('logStats.pending_amount', 500)
        );
    }

    public function test_admin_can_force_release_pending_log(): void
    {
        $log = OfferwallLog::create([
            'user_id' => $this->user->id,
            'provider' => 'TimeWall',
            'transaction_id' => 'TX_FORCE_456',
            'amount' => 250,
            'status' => 'pending',
            'release_time' => now()->addHours(20),
        ]);

        $this->assertEquals(500, $this->user->pending_balance);
        $this->assertEquals(100, $this->user->main_balance);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.offerwalls.logs.release', $log->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $log->refresh();
        $this->assertEquals('approved', $log->status);

        $this->user->refresh();
        $this->assertEquals(250, $this->user->pending_balance);
        $this->assertEquals(350, $this->user->main_balance);
    }

    public function test_admin_cannot_release_non_pending_log(): void
    {
        $log = OfferwallLog::create([
            'user_id' => $this->user->id,
            'provider' => 'TimeWall',
            'transaction_id' => 'TX_ALREADY_APPROVED',
            'amount' => 100,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.offerwalls.logs.release', $log->id));

        $response->assertSessionHasErrors('release');
    }

    public function test_admin_can_cleanup_old_logs_and_pending_are_strictly_preserved(): void
    {
        // 1. Old approved log (40 days old) -> should be deleted
        $oldApproved = OfferwallLog::create([
            'user_id' => $this->user->id,
            'provider' => 'CPALead',
            'transaction_id' => 'TX_OLD_APP',
            'amount' => 100,
            'status' => 'approved',
        ]);
        OfferwallLog::where('id', $oldApproved->id)->update(['created_at' => now()->subDays(40)]);

        // 2. Old reversed log (35 days old) -> should be deleted
        $oldReversed = OfferwallLog::create([
            'user_id' => $this->user->id,
            'provider' => 'CPALead',
            'transaction_id' => 'TX_OLD_REV',
            'amount' => 50,
            'status' => 'reversed',
        ]);
        OfferwallLog::where('id', $oldReversed->id)->update(['created_at' => now()->subDays(35)]);

        // 3. Recent approved log (5 days old) -> should be kept
        $recentApproved = OfferwallLog::create([
            'user_id' => $this->user->id,
            'provider' => 'CPALead',
            'transaction_id' => 'TX_RECENT_APP',
            'amount' => 200,
            'status' => 'approved',
        ]);
        OfferwallLog::where('id', $recentApproved->id)->update(['created_at' => now()->subDays(5)]);

        // 4. Old pending log (35 days old) -> MUST BE PRESERVED!
        $oldPending = OfferwallLog::create([
            'user_id' => $this->user->id,
            'provider' => 'CPALead',
            'transaction_id' => 'TX_OLD_PEND',
            'amount' => 300,
            'status' => 'pending',
        ]);
        OfferwallLog::where('id', $oldPending->id)->update(['created_at' => now()->subDays(35)]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.offerwalls.logs.cleanup'), ['days' => 30]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('offerwall_logs', ['id' => $oldApproved->id]);
        $this->assertDatabaseMissing('offerwall_logs', ['id' => $oldReversed->id]);
        $this->assertDatabaseHas('offerwall_logs', ['id' => $recentApproved->id]);
        $this->assertDatabaseHas('offerwall_logs', ['id' => $oldPending->id]); // Protected!
    }

    public function test_artisan_cleanup_command_executes_safely(): void
    {
        $oldApproved = OfferwallLog::create([
            'user_id' => $this->user->id,
            'provider' => 'Notik',
            'transaction_id' => 'TX_CMD_OLD',
            'amount' => 150,
            'status' => 'approved',
        ]);
        OfferwallLog::where('id', $oldApproved->id)->update(['created_at' => now()->subDays(45)]);

        $pending = OfferwallLog::create([
            'user_id' => $this->user->id,
            'provider' => 'Notik',
            'transaction_id' => 'TX_CMD_PEND',
            'amount' => 150,
            'status' => 'pending',
        ]);
        OfferwallLog::where('id', $pending->id)->update(['created_at' => now()->subDays(45)]);

        $this->artisan('offerwall:cleanup-logs --days=30')
            ->expectsOutputToContain('Cleaned 1 offerwall log(s)')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('offerwall_logs', ['id' => $oldApproved->id]);
        $this->assertDatabaseHas('offerwall_logs', ['id' => $pending->id]);
    }
}
