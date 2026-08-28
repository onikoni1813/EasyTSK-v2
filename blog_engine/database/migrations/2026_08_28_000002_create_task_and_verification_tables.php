<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add task timer, adblock settings, and ads.txt to be_sites if not already added
        if (!Schema::hasColumn('be_sites', 'task_timer_seconds')) {
            Schema::table('be_sites', function (Blueprint $table) {
                $table->unsignedSmallInteger('task_timer_seconds')->default(60)->after('theme_layout');
                $table->boolean('task_reward_enabled')->default(true)->after('task_timer_seconds');
                $table->boolean('adblock_detection_enabled')->default(true)->after('task_reward_enabled');
                $table->mediumText('ads_txt')->nullable()->after('footer_scripts');
            });
        }

        // Create Root Verification Files table
        if (!Schema::hasTable('be_root_files')) {
            Schema::create('be_root_files', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('be_sites')->cascadeOnDelete();
                $table->string('filename'); // e.g. sw.js, monetag.html, google12345.html
                $table->longText('content');
                $table->string('mime_type')->default('text/plain'); // text/javascript, text/html, text/plain
                $table->timestamps();

                $table->unique(['site_id', 'filename']);
            });
        }

        // Create Task Verification Codes table
        if (!Schema::hasTable('be_task_codes')) {
            Schema::create('be_task_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id')->constrained('be_sites')->cascadeOnDelete();
                $table->foreignId('post_id')->nullable()->constrained('be_posts')->nullOnDelete();
                $table->string('session_token', 64)->index();
                $table->string('code', 20)->unique()->index();
                $table->string('ip_hash', 64)->nullable();
                $table->unsignedSmallInteger('dwell_time_seconds')->default(60);
                $table->dateTime('started_at')->nullable();
                $table->dateTime('generated_at')->nullable();
                $table->boolean('is_used')->default(false)->index();
                $table->dateTime('used_at')->nullable();
                $table->dateTime('expires_at')->nullable()->index();
                $table->timestamps();

                $table->index(['site_id', 'session_token']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('be_task_codes');
        Schema::dropIfExists('be_root_files');
        if (Schema::hasColumn('be_sites', 'task_timer_seconds')) {
            Schema::table('be_sites', function (Blueprint $table) {
                $table->dropColumn([
                    'task_timer_seconds',
                    'task_reward_enabled',
                    'adblock_detection_enabled',
                    'ads_txt',
                ]);
            });
        }
    }
};
