<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\ReferralTracking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    /**
     * Process referral bonus progression when a referred user earns rewards
     */
    public function recordReferredUserEarning(User $referredUser, float $earnedAmount): void
    {
        if (!$referredUser->ref_by) {
            return;
        }

        $tracking = ReferralTracking::where('referrer_id', $referredUser->ref_by)
            ->where('referred_user_id', $referredUser->id)
            ->where('status', 'locked')
            ->first();

        if (!$tracking) {
            return;
        }

        DB::transaction(function () use ($tracking, $earnedAmount) {
            $lockedTracking = ReferralTracking::where('id', $tracking->id)->lockForUpdate()->first();

            // Double check status inside lock to prevent double payout race condition
            if (!$lockedTracking || $lockedTracking->status !== 'locked') {
                return;
            }

            $lockedTracking->increment('earned_so_far', $earnedAmount);
            $lockedTracking->refresh();

            // If target amount is reached, unlock and transfer locked_balance to main_balance
            if ($lockedTracking->earned_so_far >= $lockedTracking->target_amount) {
                $lockedTracking->update(['status' => 'unlocked']);

                // Lock referrer to safely adjust balances
                $referrer = User::where('id', $lockedTracking->referrer_id)->lockForUpdate()->first();
                if ($referrer && $referrer->locked_balance >= $lockedTracking->locked_reward) {
                    $referrer->decrement('locked_balance', $lockedTracking->locked_reward);
                    $referrer->increment('main_balance', $lockedTracking->locked_reward);

                    Notification::send(
                        $referrer,
                        'Referral Bonus Unlocked! 🎁',
                        "Great news! Your referred friend reached their target. +{$lockedTracking->locked_reward} bonus points have been added to your main balance!",
                        'success',
                        '/reffer',
                        true
                    );
                }
            }
        });
    }

    /**
     * Setup referral bonus lock on new user registration with referral code
     */
    public function setupNewReferral(User $newUser, int $referrerId): void
    {
        $referrer = User::find($referrerId);
        if (!$referrer) {
            return;
        }

        $bonusAmount = (float) \App\Models\AppSetting::getByKey('referral_bonus', '500'); // Locked bonus reward
        $target = (float) \App\Models\AppSetting::getByKey('referral_target', '1000'); // Target earnings required by referred user

        DB::transaction(function () use ($newUser, $referrer, $bonusAmount, $target) {
            $newUser->update(['ref_by' => $referrer->id]);

            // Add locked bonus to referrer's locked_balance
            $referrer->increment('locked_balance', $bonusAmount);

            // Create tracking record
            ReferralTracking::create([
                'referrer_id' => $referrer->id,
                'referred_user_id' => $newUser->id,
                'locked_reward' => $bonusAmount,
                'target_amount' => $target,
                'earned_so_far' => 0,
                'status' => 'locked',
            ]);
        });
    }
}
