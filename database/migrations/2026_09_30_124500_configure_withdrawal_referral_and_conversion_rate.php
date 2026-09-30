<?php

use App\Models\AppSetting;
use App\Models\PaymentMethod;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Set conversion rate to 1,000 Points = 1 BDT
        AppSetting::setByKey('conversion_rate', '1000');

        // 2. Set default first and next withdrawal limits (20,000 Pts = 20 BDT)
        AppSetting::setByKey('first_withdraw_limit', '20000');
        AppSetting::setByKey('next_withdraw_limit', '20000');

        // 3. Set minimum unlocked referrals required for 2nd withdrawal onwards (Default: 1)
        AppSetting::setByKey('min_unlocked_referrals_for_next_withdraw', '1');

        // 4. Ensure payment method min_points are null so they cleanly fall back to global limits
        PaymentMethod::whereIn('code', ['bkash', 'nagad', 'mobile_recharge'])
            ->orWhereIn('name', ['Bkash', 'Nagad', 'Mobile Recharge', 'bKash Personal', 'Nagad Personal'])
            ->update([
                'min_points' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        AppSetting::setByKey('conversion_rate', '100');
        AppSetting::setByKey('first_withdraw_limit', '1000');
        AppSetting::setByKey('next_withdraw_limit', '500');
        AppSetting::where('key', 'min_unlocked_referrals_for_next_withdraw')->delete();
    }
};
