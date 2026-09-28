<?php

namespace App\Console\Commands;

use App\Http\Controllers\OfferwallPostbackController;
use Illuminate\Console\Command;

class ReleaseOfferwallPendingBalances extends Command
{
    protected $signature = 'offerwall:release-pending';

    protected $description = 'Release offerwall pending_balance holds whose release_time has passed into main_balance';

    public function handle(OfferwallPostbackController $offerwallPostbackController): int
    {
        $releasedCount = $offerwallPostbackController->releasePendingBalances();

        // Self-healing guard: ensure no negative balances linger
        \App\Models\User::where('pending_balance', '<', 0)->update(['pending_balance' => 0]);
        \App\Models\User::where('main_balance', '<', 0)->update(['main_balance' => 0]);

        \App\Models\AppSetting::setByKey('cron_last_run_offerwall:release-pending', now()->toDateTimeString());

        $this->info("Released {$releasedCount} offerwall pending balance(s) into main_balance.");

        return self::SUCCESS;
    }
}
