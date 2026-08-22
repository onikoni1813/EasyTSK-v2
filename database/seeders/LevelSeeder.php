<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultLevels = [
            ['level_number' => 1, 'xp_required' => 0,    'bonus_reward' => 0.00],
            ['level_number' => 2, 'xp_required' => 100,  'bonus_reward' => 10.00],
            ['level_number' => 3, 'xp_required' => 250,  'bonus_reward' => 25.00],
            ['level_number' => 4, 'xp_required' => 500,  'bonus_reward' => 50.00],
            ['level_number' => 5, 'xp_required' => 1000, 'bonus_reward' => 100.00],
        ];

        foreach ($defaultLevels as $levelData) {
            Level::firstOrCreate(
                ['level_number' => $levelData['level_number']],
                $levelData
            );
        }
    }
}
