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
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('currency')->default('BDT')->after('type'); // 'BDT', 'USDT', 'USD', etc.
            $table->string('currency_symbol')->default('৳')->after('currency'); // '৳', '$', etc.
            $table->decimal('conversion_rate', 12, 4)->nullable()->after('min_points'); // e.g. 100 or 12000
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('currency')->default('BDT')->after('amount_bdt');
            $table->string('currency_symbol')->default('৳')->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['currency', 'currency_symbol', 'conversion_rate']);
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn(['currency', 'currency_symbol']);
        });
    }
};
