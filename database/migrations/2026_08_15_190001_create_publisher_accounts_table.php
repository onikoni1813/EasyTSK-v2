<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publisher_accounts', function (Blueprint $table) {
            $table->id();
            $table->enum('network', ['adsterra', 'monetag', 'admaven', 'hilltopads', 'mybid'])->default('adsterra');
            $table->string('account_name');
            $table->string('account_id_or_email');
            $table->enum('payout_method', ['wire', 'usdt', 'paypal', 'paxum', 'webmoney'])->default('usdt');
            $table->decimal('min_payout_amount', 10, 2)->default(50.00);
            $table->enum('status', ['active', 'pending_approval', 'suspended'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publisher_accounts');
    }
};
