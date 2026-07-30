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
            $table->string('param_user_id')->nullable()->default('user_id');
            $table->string('param_amount')->nullable()->default('amount');
            $table->string('param_transaction_id')->nullable()->default('transaction_id');
            $table->string('param_status')->nullable()->default('status');
            $table->string('param_secret_key')->nullable()->default('secure');
            $table->string('status_chargeback_value')->nullable()->default('reversed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offerwalls', function (Blueprint $table) {
            $table->dropColumn([
                'param_user_id',
                'param_amount',
                'param_transaction_id',
                'param_status',
                'param_secret_key',
                'status_chargeback_value'
            ]);
        });
    }
};
