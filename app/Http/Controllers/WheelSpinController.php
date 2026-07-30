<?php

namespace App\Http\Controllers;

use App\Models\WheelSpin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WheelSpinController extends Controller
{
    /**
     * Weighted prizes — higher weight = more common
     */
    private const PRIZES = [
        ['label' => 'Try Again',  'value' => 0,   'type' => 'none',   'weight' => 35, 'color' => '#1e293b', 'text_color' => '#64748b'],
        ['label' => '10 Points',  'value' => 10,  'type' => 'points', 'weight' => 25, 'color' => '#312e81', 'text_color' => '#a5b4fc'],
        ['label' => '25 Points',  'value' => 25,  'type' => 'points', 'weight' => 18, 'color' => '#4c1d95', 'text_color' => '#c4b5fd'],
        ['label' => '50 Points',  'value' => 50,  'type' => 'points', 'weight' => 12, 'color' => '#164e63', 'text_color' => '#67e8f9'],
        ['label' => '100 Points', 'value' => 100, 'type' => 'points', 'weight' => 7,  'color' => '#064e3b', 'text_color' => '#6ee7b7'],
        ['label' => '200 Points', 'value' => 200, 'type' => 'points', 'weight' => 2,  'color' => '#78350f', 'text_color' => '#fcd34d'],
        ['label' => '500 Points', 'value' => 500, 'type' => 'points', 'weight' => 1,  'color' => '#881337', 'text_color' => '#fda4af'],
    ];

    public function spin(Request $request)
    {
        try {
            $result = DB::transaction(function () {
                $user = \App\Models\User::where('id', Auth::id())->lockForUpdate()->first();

                if (!$user->canSpin()) {
                    throw new \Exception('NO_SPIN');
                }

                $prize = $this->weightedRandom(self::PRIZES);

                // Record the spin
                WheelSpin::create([
                    'user_id'     => $user->id,
                    'prize_label' => $prize['label'],
                    'prize_value' => $prize['value'],
                    'prize_type'  => $prize['type'],
                ]);

                // Award points if applicable
                if ($prize['type'] === 'points' && $prize['value'] > 0) {
                    $user->increment('main_balance', $prize['value']);
                    $user->addXp((int) ($prize['value'] / 10));
                    \App\Models\Transaction::log($user, 'credit', (float) $prize['value'], "Reward from Wheel Spin ({$prize['label']})", 'spin');
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
        }, self::PRIZES);

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
