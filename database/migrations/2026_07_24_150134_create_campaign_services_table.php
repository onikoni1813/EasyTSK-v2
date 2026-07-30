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
        Schema::create('campaign_services', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // e.g. Facebook
            $table->string('action'); // e.g. Like
            $table->decimal('clicker_reward', 8, 2);
            $table->decimal('creator_cost', 8, 2);
            $table->boolean('requires_proof')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_services');
    }
};
