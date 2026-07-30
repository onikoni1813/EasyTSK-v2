<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('description')->nullable();
            $table->decimal('reward_points', 10, 2)->default(0);
            $table->unsignedInteger('max_uses')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('promo_code_uses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promo_code_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'promo_code_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_code_uses');
        Schema::dropIfExists('promo_codes');
    }
};
