<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 10)->nullable()->unique()->after('ref_by');
            $table->decimal('risk_score', 5, 2)->default(0)->after('referral_code');
            $table->boolean('is_banned')->default(false)->after('risk_score');
            $table->timestamp('spin_available_at')->nullable()->after('is_banned');
            $table->unsignedSmallInteger('total_spins_used')->default(0)->after('spin_available_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['referral_code', 'risk_score', 'is_banned', 'spin_available_at', 'total_spins_used']);
        });
    }
};
