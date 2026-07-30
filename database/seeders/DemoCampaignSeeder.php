<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Campaign;
use App\Models\CampaignService;

class DemoCampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        if (!$user) {
            $this->command->info('No user found to assign campaigns.');
            return;
        }

        $service = CampaignService::first();
        $serviceId = $service ? $service->id : null;

        $types = ['website', 'telegram', 'youtube', 'facebook', 'other'];
        $statuses = ['pending', 'active', 'completed', 'rejected', 'paused'];

        for ($i = 1; $i <= 8; $i++) {
            Campaign::create([
                'user_id' => $user->id,
                'campaign_service_id' => $serviceId,
                'title' => 'Demo Campaign ' . $i,
                'description' => 'This is a demo campaign description for testing history.',
                'target_url' => 'https://example.com/demo-' . $i,
                'type' => $types[array_rand($types)],
                'action' => 'Visit',
                'target_clicks' => rand(50, 500),
                'cost_per_click' => 2,
                'budget_points' => rand(100, 1000),
                'status' => $statuses[array_rand($statuses)],
                'created_at' => now()->subDays(rand(1, 30)),
                'updated_at' => now()->subDays(rand(1, 10))
            ]);
        }

        $this->command->info('Successfully inserted 8 demo campaigns for User ID: ' . $user->id);
    }
}
