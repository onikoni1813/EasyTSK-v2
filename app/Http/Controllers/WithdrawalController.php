<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\PaymentMethod;
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

        // Fetch active payment methods configured by Admin
        $paymentMethods = PaymentMethod::active()->orderBy('order')->orderBy('id')->get()->map(function(PaymentMethod $m) use ($isFirstWithdrawal, $firstWithdrawLimit, $nextWithdrawLimit, $conversionRate) {
            $defaultMin = $isFirstWithdrawal ? $firstWithdrawLimit : $nextWithdrawLimit;
            return [
                'id'                  => $m->id,
                'name'                => $m->name,
                'code'                => $m->code,
                'type'                => $m->type,
                'currency'            => $m->currency ?? 'BDT',
                'currency_symbol'     => $m->currency_symbol ?? '৳',
                'conversion_rate'     => $m->conversion_rate !== null && $m->conversion_rate > 0 ? (float) $m->conversion_rate : (float) $conversionRate,
                'min_points'          => $m->min_points !== null ? (int) $m->min_points : (int) $defaultMin,
                'fixed_charge'        => (float) $m->fixed_charge,
                'charge_percent'      => (float) $m->charge_percent,
                'account_placeholder' => $m->account_placeholder,
                'instructions'        => $m->instructions,
                'icon'                => $m->icon,
            ];
        });

        $withdrawals = Withdrawal::where('user_id', $user->id)->latest()->take(5)->get();

        $userStats = [
            'totalWithdrawnCoins'   => (float) Withdrawal::where('user_id', $user->id)->where('status', 'paid')->sum('amount_coins'),
            'totalWithdrawnBdt'     => (float) Withdrawal::where('user_id', $user->id)->where('status', 'paid')->sum('amount_bdt'),
            'pendingWithdrawnCoins' => (float) Withdrawal::where('user_id', $user->id)->where('status', 'pending')->sum('amount_coins'),
            'pendingWithdrawnBdt'   => (float) Withdrawal::where('user_id', $user->id)->where('status', 'pending')->sum('amount_bdt'),
            'totalCount'            => Withdrawal::where('user_id', $user->id)->count(),
        ];

        return Inertia::render('Withdraw/Index', [
            'mainBalance' => (float) $user->main_balance,
            'canWithdraw' => $canWithdraw,
            'hasPending' => $hasPending,
            'isHealthTooLow' => $isHealthTooLow,
            'minWithdrawHealth' => $minWithdrawHealth,
            'remainingSeconds' => $remainingSeconds,
            'conversionRate' => $conversionRate,
            'minWithdrawCoins' => $minWithdrawCoins,
            'paymentMethods' => $paymentMethods,
            'savedMethod' => $user->payment_method,
            'savedNumber' => $user->payment_number,
            'hasRecoveryPin' => !empty($user->recovery_pin),
            'userStats' => $userStats,
            'withdrawals' => $withdrawals,
        ]);
    }

    public function requestWithdrawal(Request $request)
    {
        $request->validate([
            'amount_coins' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'account_details' => 'required|string',
        ]);

        $coins = (float) $request->amount_coins;

        // Find active payment method
        $method = PaymentMethod::active()
            ->where(function($q) use ($request) {
                $q->where('code', $request->payment_method)
                  ->orWhere('name', $request->payment_method);
            })
            ->first();

        if (!$method) {
            return back()->withErrors(['payment_method' => 'The selected payment method is invalid or currently disabled by admin.']);
        }

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

            // Dynamic per-method limit override or global limits
            if ($method->min_points !== null && $method->min_points > 0) {
                $minWithdrawCoins = (int) $method->min_points;
            } else {
                $minWithdrawCoins = $isFirstWithdrawal ? $firstWithdrawLimit : $nextWithdrawLimit;
            }

            // Dynamic per-method charges calculation
            $chargeCoins = 0;
            if ($method->fixed_charge > 0) {
                $chargeCoins += (float) $method->fixed_charge;
            }
            if ($method->charge_percent > 0) {
                $chargeCoins += ($coins * (float) $method->charge_percent) / 100;
            }

            if ($coins < $minWithdrawCoins) {
                throw new \Exception("Minimum payout for {$method->name} is {$minWithdrawCoins} points.");
            }

            if ($lockedUser->main_balance < $coins) {
                throw new \Exception('Insufficient main balance.');
            }

            $netCoins = $coins - $chargeCoins;
            if ($netCoins < 0) {
                throw new \Exception('Withdrawal amount is too low to cover the charge.');
            }

            $rate = $method->conversion_rate !== null && $method->conversion_rate > 0 
                ? (float) $method->conversion_rate 
                : (float) AppSetting::getByKey('conversion_rate', 100);

            $payoutAmount = round($netCoins / $rate, 2);
            $currency = $method->currency ?? 'BDT';
            $currencySymbol = $method->currency_symbol ?? '৳';

            $lockedUser->setAttribute('main_balance', $lockedUser->main_balance - $coins);
            $lockedUser->last_withdrawal_at = \Illuminate\Support\Carbon::now();
            $lockedUser->save();

            $withdrawal = Withdrawal::create([
                'user_id'         => $lockedUser->id,
                'amount_coins'    => $netCoins, // Store the net coins they will receive in value
                'charge_coins'    => $chargeCoins,
                'amount_bdt'      => $payoutAmount,
                'currency'        => $currency,
                'currency_symbol' => $currencySymbol,
                'payment_method'  => $method->name,
                'account_details' => $request->account_details,
                'status'          => 'pending',
            ]);

            \App\Models\Transaction::log($lockedUser, 'debit', $coins, "Withdrawal Request (#{$withdrawal->id}) via {$method->name}", 'withdrawal', (string)$withdrawal->id);
            \App\Models\Notification::send(
                $lockedUser,
                'Withdrawal Request Submitted ⏳',
                "Your payout request of {$currencySymbol}{$payoutAmount} {$currency} via {$method->name} is under review. 24-hour cooldown initiated.",
                'info',
                '/withdraw-history',
                true
            );

            DB::commit();

            // Send Telegram Admin Notification
            \App\Services\TelegramService::sendAdminWithdrawalAlert($withdrawal);

            return back();
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

        // Validate active payment method
        $validMethod = PaymentMethod::active()
            ->where(function($q) use ($request) {
                $q->where('code', $request->payment_method)
                  ->orWhere('name', $request->payment_method);
            })
            ->first();

        if (!$validMethod) {
            return back()->withErrors(['payment_method' => 'Selected payment method is currently disabled.']);
        }

        if ($user->recovery_pin && $user->recovery_pin !== $request->recovery_pin) {
            return back()->withErrors(['recovery_pin' => 'Invalid 4-digit PIN. Payment details protected.']);
        }

        $updateData = [
            'payment_method' => $validMethod->name,
            'payment_number' => $request->payment_number,
        ];

        if (empty($user->recovery_pin)) {
            $updateData['recovery_pin'] = $request->recovery_pin;
        }

        $user->update($updateData);

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
