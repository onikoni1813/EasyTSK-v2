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
        if (!Schema::hasColumn('notifications', 'is_popup')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->boolean('is_popup')->default(false)->after('type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('notifications', 'is_popup')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('is_popup');
            });
        }
    }
};
