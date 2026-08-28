<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Safely extend the enum in MySQL to add 'blog_reward' without affecting any existing records
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tasks MODIFY COLUMN type ENUM('shortlink', 'secret_code', 'social', 'user_ad', 'blog_reward') NOT NULL DEFAULT 'shortlink'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tasks MODIFY COLUMN type ENUM('shortlink', 'secret_code', 'social', 'user_ad') NOT NULL DEFAULT 'shortlink'");
        }
    }
};
