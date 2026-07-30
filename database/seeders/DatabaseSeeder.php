<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin User
        User::create([
            'name' => 'Admin Controller',
            'email' => 'admin@easytsk.com',
            'password' => Hash::make('admin1234'),
            'recovery_pin' => '1234',
            'device_hash' => 'seed_admin_device_hash_001',
            'role' => 'admin',
            'main_balance' => 0,
            'pending_balance' => 0,
            'locked_balance' => 0,
        ]);

        // Sample Demo User
        User::create([
            'name' => 'John Tasker',
            'email' => 'user@easytsk.com',
            'password' => Hash::make('user1234'),
            'recovery_pin' => '1234',
            'device_hash' => 'seed_user_device_hash_002',
            'role' => 'user',
            'main_balance' => 150.00,
            'pending_balance' => 50.00,
            'locked_balance' => 500.00,
            'level' => 1,
            'xp_points' => 40,
        ]);

        // Default App Settings
        AppSetting::setByKey('conversion_rate', '100'); // 100 points = 1 BDT
        AppSetting::setByKey('happy_hour', 'false');

        // Sample Tasks
        Task::create([
            'title' => 'Visit Provider A Shortlink',
            'description' => 'Complete the shortlink verification to earn 15 points.',
            'type' => 'shortlink',
            'provider_name' => 'Provider A',
            'target_url' => 'https://clk.sh',
            'reward_coins' => 15.00,
            'reward_xp' => 15,
            'daily_ip_limit' => 3,
            'status' => 'active',
        ]);

        Task::create([
            'title' => 'Telegram Secret Code Task',
            'description' => 'Join our Telegram channel and find today\'s secret code.',
            'type' => 'secret_code',
            'provider_name' => 'Telegram',
            'secret_code' => 'EASY2026',
            'reward_coins' => 25.00,
            'reward_xp' => 20,
            'daily_ip_limit' => 1,
            'status' => 'active',
        ]);

        Task::create([
            'title' => 'Subscribe to YouTube Channel',
            'description' => 'Subscribe, take a screenshot, and upload proof for review.',
            'type' => 'social',
            'provider_name' => 'YouTube',
            'reward_coins' => 50.00,
            'reward_xp' => 30,
            'daily_ip_limit' => 1,
            'status' => 'active',
        ]);
    }
}
