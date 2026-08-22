<?php

namespace Database\Seeders;

use App\Models\SiteType;
use Illuminate\Database\Seeder;

class SiteTypeSeeder extends Seeder
{
    public function run(): void
    {
        SiteType::firstOrCreate(['slug' => 'tools'], [
            'name' => 'Tools',
            'description' => 'Interactive browser-based tools and utilities',
            'icon' => '🛠️',
        ]);

        SiteType::firstOrCreate(['slug' => 'guides'], [
            'name' => 'Guides',
            'description' => 'Tutorials, how-to articles, and explainer guides',
            'icon' => '📚',
        ]);

        SiteType::firstOrCreate(['slug' => 'education'], [
            'name' => 'Education',
            'description' => 'Educational courses, quizzes, and learning modules',
            'icon' => '🎓',
        ]);
    }
}
