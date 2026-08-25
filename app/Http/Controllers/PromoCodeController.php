<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\PromoCode;
use App\Models\PromoCodeUse;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PromoCodeController extends Controller
{
    public function redeem(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        return DB::transaction(function () use ($user, $request) {
            $promo = PromoCode::where('code', strtoupper(trim($request->code)))
                ->lockForUpdate()
                ->first();

            if (!$promo || !$promo->isAvailable()) {
                return back()->withErrors(['promo_code' => 'Invalid or expired promo code.']);
            }

            // Check if user already used this code
            $alreadyUsed = PromoCodeUse::where('user_id', $user->id)
                ->where('promo_code_id', $promo->id)
                ->exists();

            if ($alreadyUsed) {
                return back()->withErrors(['promo_code' => 'You have already used this promo code.']);
            }

            PromoCodeUse::create([
                'user_id'       => $user->id,
                'promo_code_id' => $promo->id,
            ]);

            $promo->used_count += 1;
            $promo->save();

            // Log transaction BEFORE balance increment so balance_before and balance_after are calculated correctly
            Transaction::log($user, 'credit', (float) $promo->reward_points, "Promo Code Redeemed: {$promo->code}", 'promo', (string) $promo->id);

            $user->increment('main_balance', $promo->reward_points);
            $user->addXp(5);
            $user->refresh();

            Notification::send(
                $user,
                'Promo Code Redeemed! 🎁',
                "You redeemed promo code '{$promo->code}' for +{$promo->reward_points} bonus points!",
                'success',
                null,
                true
            );

            return back();
        });
    }
}

