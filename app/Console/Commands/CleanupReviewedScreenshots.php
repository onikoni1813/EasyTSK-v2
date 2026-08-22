<?php

namespace App\Console\Commands;

use App\Services\StorageSaverService;
use Illuminate\Console\Command;

class CleanupReviewedScreenshots extends Command
{
    protected $signature = 'proofs:cleanup-screenshots';

    protected $description = 'Delete leftover proof screenshot files for already-reviewed (approved/rejected) task submissions';

    public function handle(StorageSaverService $storageSaverService): int
    {
        $deletedCount = $storageSaverService->cleanupReviewedScreenshots();

        \App\Models\AppSetting::setByKey('cron_last_run_proofs:cleanup-screenshots', now()->toDateTimeString());

        $this->info("Deleted {$deletedCount} leftover reviewed screenshot file(s).");

        return self::SUCCESS;
    }
}
