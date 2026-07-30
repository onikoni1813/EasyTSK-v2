<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CampaignServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            ['platform' => 'Facebook', 'action' => 'Like', 'creator_cost' => 5.0, 'clicker_reward' => 2.0],
            ['platform' => 'Facebook', 'action' => 'Comment', 'creator_cost' => 10.0, 'clicker_reward' => 4.0],
            ['platform' => 'Facebook', 'action' => 'Share', 'creator_cost' => 8.0, 'clicker_reward' => 3.0],
            ['platform' => 'Facebook', 'action' => 'Follow/Subscribe', 'creator_cost' => 6.0, 'clicker_reward' => 2.5],
            
            ['platform' => 'YouTube', 'action' => 'Like', 'creator_cost' => 5.0, 'clicker_reward' => 2.0],
            ['platform' => 'YouTube', 'action' => 'Comment', 'creator_cost' => 10.0, 'clicker_reward' => 4.0],
            ['platform' => 'YouTube', 'action' => 'Subscribe', 'creator_cost' => 15.0, 'clicker_reward' => 6.0],

            ['platform' => 'Website', 'action' => 'Visit', 'creator_cost' => 2.0, 'clicker_reward' => 1.0],
            ['platform' => 'Telegram', 'action' => 'Join', 'creator_cost' => 5.0, 'clicker_reward' => 2.0],
        ];

        foreach ($services as $service) {
            \App\Models\CampaignService::create($service);
        }
    }
}
