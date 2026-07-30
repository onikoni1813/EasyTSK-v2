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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('google_id')->nullable()->unique();
            $table->string('device_hash')->nullable()->unique();
            $table->string('recovery_pin')->nullable();
            $table->decimal('main_balance', 10, 2)->default(0);
            $table->decimal('pending_balance', 10, 2)->default(0);
            $table->decimal('locked_balance', 10, 2)->default(0);
            $table->integer('level')->default(1);
            $table->integer('xp_points')->default(0);
            $table->foreignId('ref_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('role', ['user', 'admin'])->default('user');
            $table->string('payment_method')->nullable();
            $table->string('payment_number')->nullable();
            $table->boolean('has_claimed_welcome_bonus')->default(false);
            $table->timestamp('last_withdrawal_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
