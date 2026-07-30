<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ReferralContest;
use App\Models\User;
use App\Services\ReferralContestService;

echo "--- Testing Referral Contest Logic ---\n";

// Create or find active contest
$contest = ReferralContest::firstOrCreate(
    ['title' => 'Weekly Top Referrer Contest #1'],
    [
        'start_date' => now()->subDays(1),
        'end_date' => now()->addDays(6),
        'min_unlocked_required' => 1,
        'prizes' => [
            ['rank' => 1, 'reward' => 5000],
            ['rank' => 2, 'reward' => 2500],
            ['rank' => 3, 'reward' => 1500],
        ],
        'status' => 'active',
    ]
);

echo "Contest ID: {$contest->id}\n";
echo "Title: {$contest->title}\n";

$service = app(ReferralContestService::class);
$data = $service->getLeaderboard($contest, null, true);

echo "Leaderboard Count: " . count($data['leaderboard']) . "\n";
echo "SUCCESS!\n";
