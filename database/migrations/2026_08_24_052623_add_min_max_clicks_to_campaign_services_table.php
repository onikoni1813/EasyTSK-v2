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
        Schema::table('campaign_services', function (Blueprint $table) {
            $table->unsignedInteger('min_clicks')->default(1)->after('creator_cost');
            $table->unsignedInteger('max_clicks')->default(5000)->after('min_clicks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_services', function (Blueprint $table) {
            $table->dropColumn(['min_clicks', 'max_clicks']);
        });
    }
};
