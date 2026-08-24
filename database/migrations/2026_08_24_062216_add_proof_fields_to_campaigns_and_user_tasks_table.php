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
            if (!Schema::hasColumn('campaigns', 'proof_type')) {
                $table->string('proof_type', 50)->default('screenshot')->after('action');
            }
            if (!Schema::hasColumn('campaigns', 'proof_instruction')) {
                $table->text('proof_instruction')->nullable()->after('proof_type');
            }
            if (!Schema::hasColumn('campaigns', 'secret_code')) {
                $table->string('secret_code', 255)->nullable()->after('proof_instruction');
            }
        });

        Schema::table('user_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('user_tasks', 'task_id')) {
                $table->foreignId('task_id')->nullable()->change();
            }
            if (!Schema::hasColumn('user_tasks', 'campaign_id')) {
                $table->foreignId('campaign_id')->nullable()->after('task_id')->constrained('campaigns')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('user_tasks', 'campaign_id')) {
                $table->dropForeign(['campaign_id']);
                $table->dropColumn('campaign_id');
            }
            if (Schema::hasColumn('user_tasks', 'task_id')) {
                $table->foreignId('task_id')->nullable(false)->change();
            }
        });

        Schema::table('campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('campaigns', 'secret_code')) {
                $table->dropColumn('secret_code');
            }
            if (Schema::hasColumn('campaigns', 'proof_instruction')) {
                $table->dropColumn('proof_instruction');
            }
            if (Schema::hasColumn('campaigns', 'proof_type')) {
                $table->dropColumn('proof_type');
            }
        });
    }
};
