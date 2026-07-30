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
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('campaign_service_id')->nullable()->constrained('campaign_services')->nullOnDelete();
            // We keep type and action for legacy campaigns, but they can be nullable
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['campaign_service_id']);
            $table->dropColumn('campaign_service_id');
        });
    }
};
