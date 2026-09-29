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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'has_claimed_push_bonus')) {
                $table->boolean('has_claimed_push_bonus')->default(false)->after('has_claimed_welcome_bonus');
                $table->index('has_claimed_push_bonus');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'has_claimed_push_bonus')) {
                $table->dropIndex(['has_claimed_push_bonus']);
                $table->dropColumn('has_claimed_push_bonus');
            }
        });
    }
};
