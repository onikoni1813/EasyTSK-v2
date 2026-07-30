<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WithdrawalController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $canWithdraw = true;
        $remainingSeconds = 0;

        $minWithdrawHealth = (int) AppSetting::getByKey('min_withdrawal_health', 40);
        $isHealthTooLow = ($user->health ?? 100) <= $minWithdrawHealth;

        if ($user->last_withdrawal_at) {
            $cooldownEnd = Carbon::parse($user->last_withdrawal_at)->addHours(24);
            if (now()->lessThan($cooldownEnd)) {
                $canWithdraw = false;
                $remainingSeconds = now()->diffInSeconds($cooldownEnd);
            }
        }

        $conversionRate = (float) AppSetting::getByKey('conversion_rate', 100);

        $hasPending = Withdrawal::where('user_id', $user->id)->where('status', 'pending')->exists();
        if ($hasPending || $isHealthTooLow) {
            $canWithdraw = false;
        }

        $isFirstWithdrawal = Withdrawal::where('user_id', $user->id)->where('status', '!=', 'rejected')->count() === 0;
        
        $firstWithdrawLimit = (int) AppSetting::getByKey('first_withdraw_limit', 1000);
        $nextWithdrawLimit = (int) AppSetting::getByKey('next_withdraw_limit', 500);

        $minWithdrawCoins = $isFirstWithdrawal ? $firstWithdrawLimit : $nextWithdrawLimit;

        $withdrawalChargePercent = (float) AppSetting::getByKey('withdrawal_charge_percent', 0);
        $mobileRechargeMinLimit = (int) AppSetting::getByKey('mobile_recharge_min_limit', 500);
        $mobileRechargeFixedCharge = (float) AppSetting::getByKey('mobile_recharge_fixed_charge', 10);

        $withdrawals = Withdrawal::where('user_id', $user->id)->latest()->take(5)->get();

        return Inertia::render('Withdraw/Index', [
            'mainBalance' => (float) $user->main_balance,
            'canWithdraw' => $canWithdraw,
            'hasPending' => $hasPending,
            'isHealthTooLow' => $isHealthTooLow,
            'minWithdrawHealth' => $minWithdrawHealth,
            'remainingSeconds' => $remainingSeconds,
            'conversionRate' => $conversionRate,
            'minWithdrawCoins' => $minWithdrawCoins,
            'withdrawalChargePercent' => $withdrawalChargePercent,
            'mobileRechargeMinLimit' => $mobileRechargeMinLimit,
            'mobileRechargeFixedCharge' => $mobileRechargeFixedCharge,
            'savedMethod' => $user->payment_method,
            'savedNumber' => $user->payment_number,
            'withdrawals' => $withdrawals,
        ]);
    }

    public function requestWithdrawal(Request $request)
    {
        $request->validate([
            'amount_coins' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:bKash,Nagad,Rocket,Mobile Recharge',
            'account_details' => 'required|string',
        ]);

        $coins = (float) $request->amount_coins;

        try {
            DB::beginTransaction();

            /** @var \App\Models\User $lockedUser */
            $lockedUser = \App\Models\User::where('id', Auth::id())->lockForUpdate()->first();

            $minWithdrawHealth = (int) AppSetting::getByKey('min_withdrawal_health', 40);
            if (($lockedUser->health ?? 100) <= $minWithdrawHealth) {
                throw new \Exception("Withdrawal Restricted: Your account health is currently at {$lockedUser->health}%. Minimum required health is above {$minWithdrawHealth}%. Complete tasks to restore health.");
            }

            if ($lockedUser->last_withdrawal_at) {
                $cooldownEnd = Carbon::parse($lockedUser->last_withdrawal_at)->addHours(24);
                if (now()->lessThan($cooldownEnd)) {
                    throw new \Exception('24-Hour Cooldown is active. Please wait until the timer expires.');
                }
            }

            $hasPending = Withdrawal::where('user_id', $lockedUser->id)->where('status', 'pending')->exists();
            if ($hasPending) {
                throw new \Exception('You already have a pending withdrawal request.');
            }

            $isFirstWithdrawal = Withdrawal::where('user_id', $lockedUser->id)->where('status', '!=', 'rejected')->count() === 0;
            
            $firstWithdrawLimit = (int) AppSetting::getByKey('first_withdraw_limit', 1000);
            $nextWithdrawLimit = (int) AppSetting::getByKey('next_withdraw_limit', 500);

            if ($request->payment_method === 'Mobile Recharge') {
                $minWithdrawCoins = (int) AppSetting::getByKey('mobile_recharge_min_limit', 500);
                $chargeCoins = (float) AppSetting::getByKey('mobile_recharge_fixed_charge', 10);
            } else {
                $minWithdrawCoins = $isFirstWithdrawal ? $firstWithdrawLimit : $nextWithdrawLimit;
                $chargePercent = (float) AppSetting::getByKey('withdrawal_charge_percent', 0);
                $chargeCoins = ($coins * $chargePercent) / 100;
            }

            if ($coins < $minWithdrawCoins) {
                throw new \Exception("Minimum payout for this method is {$minWithdrawCoins} points.");
            }

            if ($lockedUser->main_balance < $coins) {
                throw new \Exception('Insufficient main balance.');
            }

            $netCoins = $coins - $chargeCoins;
            if ($netCoins < 0) {
                throw new \Exception('Withdrawal amount is too low to cover the charge.');
            }

            $rate = (float) AppSetting::getByKey('conversion_rate', 100);
            $bdtAmount = round($netCoins / $rate, 2);

            $lockedUser->setAttribute('main_balance', $lockedUser->main_balance - $coins);
            $lockedUser->last_withdrawal_at = \Illuminate\Support\Carbon::now();
            $lockedUser->save();

            $withdrawal = Withdrawal::create([
                'user_id' => $lockedUser->id,
                'amount_coins' => $netCoins, // Store the net coins they will receive in value
                'charge_coins' => $chargeCoins,
                'amount_bdt' => $bdtAmount,
                'payment_method' => $request->payment_method,
                'account_details' => $request->account_details,
                'status' => 'pending',
            ]);

            \App\Models\Transaction::log($lockedUser, 'debit', $coins, "Withdrawal Request (#{$withdrawal->id}) via {$request->payment_method}", 'withdrawal', (string)$withdrawal->id);
            \App\Models\Notification::send($lockedUser, 'Withdrawal Request Submitted ⏳', "Your payout request of {$bdtAmount} BDT via {$request->payment_method} is under review.", 'info', '/withdraw-history');

            DB::commit();

            // Send Telegram Admin Notification
            \App\Services\TelegramService::sendAdminWithdrawalAlert($withdrawal);

            return back()->with('success', 'Withdrawal request submitted successfully! 24-hour cooldown initiated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function updatePaymentDetails(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'payment_number' => 'required|string',
            'recovery_pin' => 'required|digits:4',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->recovery_pin && $user->recovery_pin !== $request->recovery_pin) {
            return back()->withErrors(['recovery_pin' => 'Invalid 4-digit PIN. Payment details protected.']);
        }

        $user->update([
            'payment_method' => $request->payment_method,
            'payment_number' => $request->payment_number,
        ]);

        return back()->with('success', 'Payment wallet details updated successfully.');
    }
    public function history()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $withdrawals = Withdrawal::where('user_id', $user->id)->latest()->get();

        return Inertia::render('Withdraw/History', [
            'withdrawals' => $withdrawals,
        ]);
    }
}
