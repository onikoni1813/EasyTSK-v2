<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referred_user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('locked_reward', 8, 2)->default(500);
            $table->decimal('target_amount', 8, 2)->default(1000);
            $table->decimal('earned_so_far', 8, 2)->default(0);
            $table->enum('status', ['locked', 'unlocked', 'claimed'])->default('locked');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_trackings');
    }
};
