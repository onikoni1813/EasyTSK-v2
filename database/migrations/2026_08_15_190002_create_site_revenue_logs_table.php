<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_revenue_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->foreignId('publisher_account_id')->nullable()->constrained('publisher_accounts')->onDelete('set null');
            $table->enum('network', ['adsterra', 'monetag', 'admaven', 'hilltopads', 'mybid'])->default('adsterra');
            $table->date('log_date');
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->decimal('revenue_usd', 10, 4)->default(0.0000);
            $table->decimal('cpm_rate', 10, 4)->default(0.0000);
            $table->enum('payment_status', ['unpaid', 'pending_payout', 'paid'])->default('unpaid');
            $table->timestamps();

            $table->unique(['site_id', 'network', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_revenue_logs');
    }
};
