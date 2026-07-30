<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['shortlink', 'secret_code', 'social', 'user_ad']);
            $table->string('provider_name')->nullable();
            $table->string('target_url')->nullable();
            $table->string('secret_code')->nullable();
            $table->decimal('reward_coins', 8, 2);
            $table->integer('reward_xp')->default(10);
            $table->integer('daily_ip_limit')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
