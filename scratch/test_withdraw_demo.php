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

// Clear all previous test notifications for a clean SINGLE demo
Notification::where('user_id', $user->id)->delete();

echo "Creating ONLY 1 Withdrawal Paid test notification for user: {$user->name} (ID: {$user->id})\n";

Notification::send(
    $user,
    'Withdrawal Paid! 💸',
    'Your payout request of 500 points via bKash has been processed successfully. Trx ID: TRX987654321',
    'success',
    '/withdraw-history'
);

echo "Withdrawal Paid notification created successfully!\n";
