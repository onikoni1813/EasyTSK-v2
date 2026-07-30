<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offerwall_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('provider'); // Notik, Timewall, AdMaven, etc.
            $table->string('transaction_id')->unique();
            $table->decimal('amount', 8, 2);
            $table->enum('status', ['pending', 'approved', 'reversed'])->default('pending');
            $table->timestamp('release_time')->nullable(); // For 24h pending hold
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offerwall_logs');
    }
};
