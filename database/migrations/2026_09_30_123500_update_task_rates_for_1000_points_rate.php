<?php

use Illuminate\Database\Migrations\Migration;
use Database\Seeders\ShortlinkTaskSeeder;

return new class extends Migration
{
    /**
     * Run the migrations to update the 12 shortlink tasks with balanced points (1000 Points = 1 BDT).
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
        // No-op
    }
};
