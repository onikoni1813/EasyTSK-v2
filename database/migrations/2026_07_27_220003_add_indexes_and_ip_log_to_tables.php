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
            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'last_login_device')) {
                $table->text('last_login_device')->nullable()->after('last_login_ip');
            }
        });

        Schema::table('user_tasks', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'idx_user_tasks_user_status');
            $table->index(['task_id', 'user_id'], 'idx_user_tasks_task_user');
        });

        Schema::table('campaign_clicks', function (Blueprint $table) {
            $table->index(['user_id', 'campaign_id'], 'idx_campaign_clicks_user_campaign');
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'idx_withdrawals_user_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login_ip', 'last_login_device']);
        });

        Schema::table('user_tasks', function (Blueprint $table) {
            $table->dropIndex('idx_user_tasks_user_status');
            $table->dropIndex('idx_user_tasks_task_user');
        });

        Schema::table('campaign_clicks', function (Blueprint $table) {
            $table->dropIndex('idx_campaign_clicks_user_campaign');
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropIndex('idx_withdrawals_user_status');
        });
    }
};
