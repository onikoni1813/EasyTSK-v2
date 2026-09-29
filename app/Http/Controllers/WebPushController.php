<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebPushController extends Controller
{
    protected WebPushService $pushService;

    public function __construct(WebPushService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * Return VAPID Public Key for client subscription.
     */
    public function getPublicKey(): JsonResponse
    {
        return response()->json([
            'success'    => true,
            'public_key' => $this->pushService->getPublicKey(),
            'is_enabled' => $this->pushService->isEnabled(),
        ]);
    }

    /**
     * Store or update client subscription.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint'         => 'required|string',
            'keys.p256dh'      => 'required_without:public_key|string|nullable',
            'keys.auth'        => 'required_without:auth_token|string|nullable',
            'public_key'       => 'nullable|string',
            'auth_token'       => 'nullable|string',
            'content_encoding' => 'nullable|string',
        ]);

        $result = $this->pushService->subscribe($validated, Auth::user(), $request);

        $message = $result['bonus_awarded']
            ? "অভিনন্দন! নোটিফিকেশন অন করায় আপনার অ্যাকাউন্টে +{$result['bonus_amount']} পয়েন্ট বোনাস যুক্ত হয়েছে!"
            : 'নোটিফিকেশন সফলভাবে চালু করা হয়েছে!';

        return response()->json([
            'success'       => true,
            'message'       => $message,
            'bonus_awarded' => $result['bonus_awarded'],
            'bonus_amount'  => $result['bonus_amount'],
            'new_balance'   => $result['new_balance'],
            'id'            => $result['subscription']->id,
        ]);
    }

    /**
     * Unsubscribe client endpoint.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        PushSubscription::where('endpoint', $validated['endpoint'])->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Unsubscribed successfully.',
        ]);
    }
}
