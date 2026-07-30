<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wheel_spins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('prize_label');        // e.g. "50 Points"
            $table->decimal('prize_value', 10, 2)->default(0);  // 0 = "Try Again"
            $table->string('prize_type')->default('points'); // points, bonus_multiplier, etc.
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wheel_spins');
    }
};
