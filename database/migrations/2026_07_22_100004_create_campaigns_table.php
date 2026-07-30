<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('target_url');
            $table->string('type')->default('website'); // website, telegram, youtube, other
            $table->decimal('budget_points', 10, 2); // total points spent
            $table->decimal('cost_per_click', 6, 2)->default(1.00); // points per click
            $table->unsignedInteger('total_clicks')->default(0);
            $table->unsignedInteger('target_clicks'); // how many clicks bought
            $table->string('status')->default('pending'); // pending, active, paused, completed, rejected
            $table->string('admin_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('campaign_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'user_id']); // one click per user per campaign
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_clicks');
        Schema::dropIfExists('campaigns');
    }
};
