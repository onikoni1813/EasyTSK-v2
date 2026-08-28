<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shortlink_sessions')) {
            Schema::create('shortlink_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('task_id')->index();
                $table->string('token', 64)->unique()->index();
                $table->string('status', 20)->default('pending'); // pending, completed, expired
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->dateTime('expires_at')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shortlink_sessions');
    }
};
