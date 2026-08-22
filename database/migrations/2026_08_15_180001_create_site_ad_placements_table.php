<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_ad_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->enum('network', ['adsterra', 'monetag', 'admaven', 'hilltopads', 'mybid', 'custom'])->default('custom');
            $table->enum('placement_slot', ['header_top', 'content_top', 'content_bottom', 'sidebar', 'footer_bottom']);
            $table->longText('ad_code')->nullable();
            $table->enum('device_target', ['all', 'desktop_only', 'mobile_only'])->default('all');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['site_id', 'placement_slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_ad_placements');
    }
};
