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
        Schema::create('referral_contests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->unsignedInteger('min_unlocked_required')->default(1);
            $table->json('prizes'); // Array of {rank: int, reward: float}
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamp('distributed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_contests');
    }
};
