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
        Schema::table('offerwalls', function (Blueprint $table) {
            if (!Schema::hasColumn('offerwalls', 'api_key')) {
                $table->string('api_key')->nullable()->after('secret_key');
            }
            if (!Schema::hasColumn('offerwalls', 'pub_id')) {
                $table->string('pub_id')->nullable()->after('api_key');
            }
            if (!Schema::hasColumn('offerwalls', 'app_id')) {
                $table->string('app_id')->nullable()->after('pub_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offerwalls', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('offerwalls', 'api_key')) {
                $columnsToDrop[] = 'api_key';
            }
            if (Schema::hasColumn('offerwalls', 'pub_id')) {
                $columnsToDrop[] = 'pub_id';
            }
            if (Schema::hasColumn('offerwalls', 'app_id')) {
                $columnsToDrop[] = 'app_id';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
