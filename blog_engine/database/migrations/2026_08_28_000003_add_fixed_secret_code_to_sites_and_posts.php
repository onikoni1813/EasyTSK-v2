<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('be_sites', 'fixed_secret_code')) {
            Schema::table('be_sites', function (Blueprint $table) {
                $table->string('fixed_secret_code')->nullable()->after('task_timer_seconds');
            });
        }

        if (!Schema::hasColumn('be_posts', 'fixed_secret_code')) {
            Schema::table('be_posts', function (Blueprint $table) {
                $table->string('fixed_secret_code')->nullable()->after('content');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('be_sites', 'fixed_secret_code')) {
            Schema::table('be_sites', function (Blueprint $table) {
                $table->dropColumn('fixed_secret_code');
            });
        }

        if (Schema::hasColumn('be_posts', 'fixed_secret_code')) {
            Schema::table('be_posts', function (Blueprint $table) {
                $table->dropColumn('fixed_secret_code');
            });
        }
    }
};
