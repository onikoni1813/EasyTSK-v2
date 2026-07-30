<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class RegenerateUserHealth extends Command
{
    protected $signature = 'health:regenerate-daily';

    protected $description = 'Passively regenerate +20 health (capped at 100) for every user, once per day';

    /**
     * Daily passive amount restored to every user, regardless of activity.
     */
    private const DAILY_REGEN_AMOUNT = 20;

    public function handle(): int
    {
        $affected = 0;

        User::where('health', '<', User::MAX_HEALTH)->each(function (User $user) use (&$affected) {
            $user->addHealth(self::DAILY_REGEN_AMOUNT);
            $affected++;
        });

        $this->info("Regenerated health for {$affected} user(s).");

        return self::SUCCESS;
    }
}
