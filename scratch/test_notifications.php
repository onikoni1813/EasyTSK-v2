<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Notification;

$user = User::first();

if (!$user) {
    echo "No user found in DB.\n";
    exit;
}

// Clear existing test notifications for fresh clean test
Notification::where('user_id', $user->id)->delete();

echo "Adding fresh test notifications for user: {$user->name} (ID: {$user->id})\n";

// 1. Level Upgrade Notification
Notification::send(
    $user,
    'Level Upgraded! ⚡',
    'Congratulations! You reached Level 2 and earned +100 bonus points!',
    'success',
    '/dashboard'
);

// 2. Referral Bonus Unlocked Notification
Notification::send(
    $user,
    'Referral Bonus Unlocked! 🎁',
    'Great news! Your referred friend reached their target. +500 bonus points have been added to your main balance!',
    'success',
    '/reffer'
);

// 3. Top Referrer Contest Winner Notification
Notification::send(
    $user,
    'Contest Champion! 🏆',
    'Congratulations! You secured Rank #1 in the Top Referrer Contest and won 1000 bonus points!',
    'success',
    '/reffer'
);

// 4. Withdrawal Paid Notification
Notification::send(
    $user,
    'Withdrawal Paid! 💸',
    'Your payout request of 500 points via bKash has been processed successfully.',
    'success',
    '/withdraw-history'
);

echo "4 Fresh Test Notifications created with correct URLs (/reffer, /withdraw-history, /dashboard)!\n";
