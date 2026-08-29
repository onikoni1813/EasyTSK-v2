<?php

namespace Database\Seeders;

use App\Models\ShortlinkProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ShortlinkProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = ShortlinkProvider::PRESETS;

        foreach ($providers as $key => $preset) {
            if ($key === 'custom') {
                continue;
            }

            $slug = Str::slug($preset['name']);

            ShortlinkProvider::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $preset['name'],
                    'api_url' => $preset['api_url'],
                    'api_key' => $preset['default_key'] ?? '',
                    'icon' => $preset['icon'] ?? '🔗',
                    'daily_limit' => 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
