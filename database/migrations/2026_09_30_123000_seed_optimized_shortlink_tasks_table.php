<?php

use Illuminate\Database\Migrations\Migration;
use Database\Seeders\ShortlinkTaskSeeder;

return new class extends Migration
{
    /**
     * Run the migrations to seed/update the 12 optimized shortlink tasks into the tasks table.
     */
    public function up(): void
    {
        $seeder = new ShortlinkTaskSeeder();
        $seeder->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep existing records or no-op
    }
};
