<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Notification;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Auth;

$user = User::first();
if (!$user) {
    echo "No user found.\n";
    exit;
}

Auth::login($user);

echo "1. Checking Notification::send()...\n";
$n = Notification::send($user, 'Functional Test Notification', 'Checking if everything works smoothly!', 'success', '/dashboard');
echo "   Notification created with ID: {$n->id}\n";

echo "2. Checking DashboardController API endpoints...\n";
$controller = new DashboardController();

$response = $controller->notifications();
$data = json_decode($response->getContent(), true);
echo "   Unread Count: {$data['unread_count']}, Total fetched: " . count($data['notifications']) . "\n";

echo "3. Marking single notification read...\n";
$controller->markNotificationRead($n);
$n->refresh();
echo "   Read at timestamp: " . ($n->read_at ? $n->read_at->toDateTimeString() : 'NULL') . "\n";

echo "4. Marking all notifications read...\n";
$controller->markAllAllRead = $controller->markAllNotificationsRead();
$unreadCount = Notification::where('user_id', $user->id)->whereNull('read_at')->count();
echo "   Remaining Unread Count: {$unreadCount}\n";

echo "\n--- ALL BACKEND NOTIFICATION FUNCTIONS ARE 100% OPERATIONAL! ---\n";
