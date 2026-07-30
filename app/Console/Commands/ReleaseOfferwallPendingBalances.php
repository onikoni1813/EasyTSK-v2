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

        $this->info("Released {$releasedCount} offerwall pending balance(s) into main_balance.");

        return self::SUCCESS;
    }
}
