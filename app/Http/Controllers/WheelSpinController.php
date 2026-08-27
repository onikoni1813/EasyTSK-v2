<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\WheelSpin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WheelSpinController extends Controller
{
    /**
     * Return dynamically configured weighted prizes
     */
    public static function getPrizes(): array
    {
        $p1 = (int) AppSetting::getByKey('wheel_slot_1', '10');
        $p2 = (int) AppSetting::getByKey('wheel_slot_2', '25');
        $p3 = (int) AppSetting::getByKey('wheel_slot_3', '50');
        $p4 = (int) AppSetting::getByKey('wheel_slot_4', '100');
        $p5 = (int) AppSetting::getByKey('wheel_slot_5', '200');
        $jackpot = (int) AppSetting::getByKey('wheel_jackpot', '500');

        return [
            ['label' => 'Try Again',              'value' => 0,        'type' => 'none',    'weight' => 35, 'color' => '#1e293b', 'text_color' => '#64748b'],
            ['label' => "{$p1} Points",          'value' => $p1,      'type' => 'points',  'weight' => 25, 'color' => '#312e81', 'text_color' => '#a5b4fc'],
            ['label' => "{$p2} Points",          'value' => $p2,      'type' => 'points',  'weight' => 18, 'color' => '#4c1d95', 'text_color' => '#c4b5fd'],
            ['label' => "{$p3} Points",          'value' => $p3,      'type' => 'points',  'weight' => 12, 'color' => '#164e63', 'text_color' => '#67e8f9'],
            ['label' => "{$p4} Points",          'value' => $p4,      'type' => 'points',  'weight' => 7,  'color' => '#064e3b', 'text_color' => '#6ee7b7'],
            ['label' => "{$p5} Points",          'value' => $p5,      'type' => 'points',  'weight' => 2,  'color' => '#78350f', 'text_color' => '#fcd34d'],
            ['label' => "🔥 JACKPOT ({$jackpot}P)", 'value' => $jackpot, 'type' => 'jackpot', 'weight' => 1,  'color' => '#881337', 'text_color' => '#fbbf24'],
        ];
    }

    public function spin(Request $request)
    {
        try {
            $result = DB::transaction(function () {
                $user = \App\Models\User::where('id', Auth::id())->lockForUpdate()->first();

                if (!$user->canSpin()) {
                    throw new \Exception('NO_SPIN');
                }

                $prizes = self::getPrizes();
                
                // If user is test user (01888800000), guarantee Jackpot on test spin!
                if ($user->phone === '01888800000') {
                    $jackpotPrize = collect($prizes)->firstWhere('type', 'jackpot');
                    $prize = $jackpotPrize ?: $this->weightedRandom($prizes);
                } else {
                    $prize = $this->weightedRandom($prizes);
                }

                // Record the spin
                WheelSpin::create([
                    'user_id'     => $user->id,
                    'prize_label' => $prize['label'],
                    'prize_value' => $prize['value'],
                    'prize_type'  => $prize['type'],
                ]);

                // Award points if applicable
                if (in_array($prize['type'], ['points', 'jackpot']) && $prize['value'] > 0) {
                    $user->increment('main_balance', $prize['value']);
                    $user->addXp((int) max(1, ($prize['value'] / 10)));
                    $desc = $prize['type'] === 'jackpot' ? "GRAND JACKPOT from Wheel Spin ({$prize['label']})" : "Reward from Wheel Spin ({$prize['label']})";
                    \App\Models\Transaction::log($user, 'credit', (float) $prize['value'], $desc, 'spin');

                    if ($prize['type'] === 'jackpot') {
                        \App\Models\Notification::send(
                            $user,
                            'JACKPOT WINNER! 💥🎰',
                            "UNBELIEVABLE! You just hit the GRAND JACKPOT on the Daily Bonus Wheel and won {$prize['value']} Points!",
                            'success',
                            '/dashboard',
                            true
                        );
                    }
                }

                // Consume the spin — null out spin_available_at
                $user->update([
                    'spin_available_at' => null,
                    'total_spins_used'  => $user->total_spins_used + 1,
                ]);

                return [
                    'prize'       => $prize,
                    'new_balance' => (float) $user->fresh()->main_balance,
                ];
            });

            return response()->json([
                'success'       => true,
                'prize'         => $result['prize'],
                'new_balance'   => $result['new_balance'],
            ]);
            
        } catch (\Exception $e) {
            if ($e->getMessage() === 'NO_SPIN') {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have a spin available yet. Complete a 7-day streak to earn one!',
                ], 403);
            }

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during your spin.',
            ], 500);
        }
    }

    /**
     * Return wheel configuration for the frontend canvas renderer
     */
    public function config()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // Strip internal 'weight' field — clients don't need probability info
        $prizes = array_map(function ($p) {
            return [
                'label'      => $p['label'],
                'value'      => $p['value'],
                'type'       => $p['type'],
                'color'      => $p['color'],
                'text_color' => $p['text_color'],
            ];
        }, self::getPrizes());

        return response()->json([
            'prizes'   => $prizes,
            'can_spin' => $user ? $user->canSpin() : false,
            'spin_at'  => $user ? $user->spin_available_at?->toIso8601String() : null,
        ]);
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    private function weightedRandom(array $items): array
    {
        $totalWeight = array_sum(array_column($items, 'weight'));
        $rand = random_int(1, $totalWeight);
        $cumulative = 0;
        foreach ($items as $item) {
            $cumulative += $item['weight'];
            if ($rand <= $cumulative) {
                return $item;
            }
        }
        return $items[0];
    }
}
