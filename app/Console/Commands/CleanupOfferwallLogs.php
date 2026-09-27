<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Models\OfferwallLog;
use Illuminate\Console\Command;

class CleanupOfferwallLogs extends Command
{
    protected $signature = 'offerwall:cleanup-logs {--days=30 : Number of days of completed history to retain}';

    protected $description = 'Delete completed (approved/reversed) offerwall logs older than the specified days to optimize database storage';

    public function handle(): int
    {
        $days = max(1, (int) ($this->option('days') ?: 30));

        // Strict safety: NEVER delete pending logs!
        $deletedCount = OfferwallLog::whereIn('status', ['approved', 'reversed'])
            ->where('created_at', '<=', now()->subDays($days))
            ->delete();

        AppSetting::setByKey('cron_last_run_offerwall:cleanup-logs', now()->toDateTimeString());

        $this->info("Cleaned {$deletedCount} offerwall log(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
