<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\AdsLabService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdsLabOfferwallController extends Controller
{
    /**
     * Get live or cached offers from AdsLab API for the current user.
     */
    public function getOffers(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $service = new AdsLabService();
        $forceRefresh = $request->boolean('refresh', false);

        $result = $service->getOffers($user, $request, $forceRefresh);

        // Compile distinct categories across offers
        $allCategories = [];
        foreach ($result['offers'] as $offer) {
            foreach ($offer['categories'] ?? [] as $cat) {
                if ($cat !== '') {
                    $allCategories[$cat] = true;
                }
            }
        }
        $categories = array_keys($allCategories);
        sort($categories);

        return response()->json([
            'success'       => $result['success'],
            'offers'        => $result['offers'],
            'total'         => count($result['offers']),
            'categories'    => $categories,
            'is_configured' => $result['is_configured'] ?? $service->isConfigured(),
            'is_cached'     => $result['is_cached'] ?? false,
            'country'       => $result['country'] ?? 'BD',
            'device_os'     => $result['device_os'] ?? 'all',
            'reward_ratio'  => $service->getRewardRatio(),
            'currency_name' => AppSetting::getByKey('currency_name', 'Coins'),
            'message'       => $result['message'] ?? '',
        ]);
    }
}
