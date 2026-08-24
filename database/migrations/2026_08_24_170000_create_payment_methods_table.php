<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. 'bKash Personal', 'Nagad Personal'
            $table->string('code')->unique(); // e.g. 'bKash', 'Nagad', 'Rocket', 'Mobile Recharge'
            $table->string('type')->default('mobile_banking'); // 'mobile_banking', 'recharge', 'crypto', 'bank', 'other'
            $table->unsignedInteger('min_points')->nullable(); // Nullable: falls back to global settings
            $table->decimal('fixed_charge', 10, 2)->default(0); // e.g. 10 Pts for recharge
            $table->decimal('charge_percent', 5, 2)->default(0); // e.g. 0% or 2%
            $table->string('account_placeholder')->default('017XXXXXXXX');
            $table->text('instructions')->nullable();
            $table->string('icon')->nullable(); // Emoji or logo identifier
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Seed initial 4 active payment methods
        $now = now();
        DB::table('payment_methods')->insert([
            [
                'name'                => 'bKash Personal',
                'code'                => 'bKash',
                'type'                => 'mobile_banking',
                'min_points'          => null,
                'fixed_charge'        => 0,
                'charge_percent'      => 0,
                'account_placeholder' => '017XXXXXXXX',
                'instructions'        => 'Enter your 11-digit personal bKash mobile number.',
                'icon'                => '🌸',
                'is_active'           => true,
                'order'               => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'name'                => 'Nagad Personal',
                'code'                => 'Nagad',
                'type'                => 'mobile_banking',
                'min_points'          => null,
                'fixed_charge'        => 0,
                'charge_percent'      => 0,
                'account_placeholder' => '017XXXXXXXX',
                'instructions'        => 'Enter your 11-digit personal Nagad mobile number.',
                'icon'                => '🟠',
                'is_active'           => true,
                'order'               => 2,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'name'                => 'Rocket Personal',
                'code'                => 'Rocket',
                'type'                => 'mobile_banking',
                'min_points'          => null,
                'fixed_charge'        => 0,
                'charge_percent'      => 0,
                'account_placeholder' => '017XXXXXXXXX (12 digits)',
                'instructions'        => 'Enter your 12-digit personal Rocket account number.',
                'icon'                => '🚀',
                'is_active'           => true,
                'order'               => 3,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'name'                => 'Mobile Recharge',
                'code'                => 'Mobile Recharge',
                'type'                => 'recharge',
                'min_points'          => 500,
                'fixed_charge'        => 10,
                'charge_percent'      => 0,
                'account_placeholder' => '017XXXXXXXX',
                'instructions'        => 'Enter Prepaid/Postpaid mobile number for instant airtime top-up.',
                'icon'                => '📱',
                'is_active'           => true,
                'order'               => 4,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
