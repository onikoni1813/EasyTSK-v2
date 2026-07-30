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
        Schema::create('offerwalls', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('iframe_url_pattern'); // e.g. https://timewall.com/offerwall?id=...&uid={user_id}
            $table->string('secret_key')->nullable();
            $table->decimal('reward_ratio', 8, 2)->default(1.00); // How much of provider currency = 1 main balance
            $table->boolean('status')->default(true);
            $table->boolean('is_api')->default(false); // If true, maybe it's not an iframe but an API
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offerwalls');
    }
};
