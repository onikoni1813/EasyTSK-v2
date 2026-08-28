<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shortlink_providers')) {
            Schema::create('shortlink_providers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('api_url');
                $table->text('api_key')->nullable();
                $table->integer('daily_limit')->default(1);
                $table->boolean('is_active')->default(true);
                $table->string('icon')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shortlink_providers');
    }
};
