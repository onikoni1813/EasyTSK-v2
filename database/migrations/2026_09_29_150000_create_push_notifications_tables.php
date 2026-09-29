<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Table for client browser push subscriptions
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('endpoint', 500)->unique();
            $table->text('public_key'); // p256dh
            $table->text('auth_token'); // auth
            $table->string('content_encoding', 30)->default('aes128gcm');
            $table->string('device_type', 30)->default('desktop'); // mobile, tablet, desktop
            $table->string('browser', 50)->nullable(); // chrome, firefox, edge, etc.
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index('device_type');
        });

        // Table for push campaign logs
        Schema::create('push_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('target_url', 500)->default('/tasks');
            $table->string('icon_url', 500)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('audience_filter', 50)->default('all'); // all, active_today, inactive_3d, inactive_7d, mobile_only, desktop_only
            $table->unsignedInteger('total_targeted')->default(0);
            $table->unsignedInteger('total_sent')->default(0);
            $table->unsignedInteger('total_failed')->default(0);
            $table->string('status', 30)->default('completed'); // completed, partial, failed
            $table->text('error_summary')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_campaigns');
        Schema::dropIfExists('push_subscriptions');
    }
};
