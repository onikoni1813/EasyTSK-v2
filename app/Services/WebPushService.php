<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\PushCampaign;
use App\Models\PushSubscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    protected ?string $publicKey;
    protected ?string $privateKey;
    protected string $subject;

    public function __construct()
    {
        $this->publicKey = AppSetting::getByKey('push_vapid_public_key');
        $this->privateKey = AppSetting::getByKey('push_vapid_private_key');
        $this->subject = AppSetting::getByKey('push_vapid_subject', 'mailto:support@easytsk.com');

        // Initialize default VAPID keys if not present
        if (empty($this->publicKey) || empty($this->privateKey)) {
            $this->publicKey = 'BBcvWCgv538mAyqTpo7Z940DkMCvMGlyra-w00ZPzyV6YUaUBUzmdsyKjLun8Np8tcxhxcqpQgo8DTo8D1ieIiI';
            $this->privateKey = 'UXevIeCIxGWcJjaFHGYqAQhlBKe116ss2C1ruiUUVwM';
            $this->subject = 'mailto:support@easytsk.com';

            AppSetting::setByKey('push_vapid_public_key', $this->publicKey, 'VAPID Public Key for Web Push');
            AppSetting::setByKey('push_vapid_private_key', $this->privateKey, 'VAPID Private Key for Web Push');
            AppSetting::setByKey('push_vapid_subject', $this->subject, 'VAPID Subject for Web Push');
            AppSetting::setByKey('push_notifications_enabled', 'true', 'Enable Web Push Notifications');
        }
    }

    public function getPublicKey(): string
    {
        return $this->publicKey ?? '';
    }

    public function isEnabled(): bool
    {
        return AppSetting::getByKey('push_notifications_enabled', 'true') === 'true';
    }

    /**
     * Create WebPush instance with VAPID authentication.
     */
    protected function getWebPushInstance(): WebPush
    {
        $auth = [
            'VAPID' => [
                'subject'    => $this->subject,
                'publicKey'  => $this->publicKey,
                'privateKey' => $this->privateKey,
            ],
        ];

        return new WebPush($auth, ['timeout' => 15]);
    }

    /**
     * Register or update client subscription.
     */
    public function subscribe(array $data, ?User $user, Request $request): array
    {
        $endpoint = $data['endpoint'];
        $publicKey = $data['keys']['p256dh'] ?? ($data['public_key'] ?? '');
        $authToken = $data['keys']['auth'] ?? ($data['auth_token'] ?? '');
        $contentEncoding = $data['content_encoding'] ?? 'aes128gcm';

        $ua = strtolower($request->userAgent() ?: '');
        $deviceType = 'desktop';
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            $deviceType = 'tablet';
        } elseif (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            $deviceType = 'mobile';
        }

        $browser = 'other';
        if (str_contains($ua, 'chrome') && !str_contains($ua, 'edg')) {
            $browser = 'chrome';
        } elseif (str_contains($ua, 'edg')) {
            $browser = 'edge';
        } elseif (str_contains($ua, 'firefox')) {
            $browser = 'firefox';
        } elseif (str_contains($ua, 'safari') && !str_contains($ua, 'chrome')) {
            $browser = 'safari';
        } elseif (str_contains($ua, 'opera') || str_contains($ua, 'opr')) {
            $browser = 'opera';
        }

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint' => $endpoint],
            [
                'user_id'          => $user?->id,
                'public_key'       => $publicKey,
                'auth_token'       => $authToken,
                'content_encoding' => $contentEncoding,
                'device_type'      => $deviceType,
                'browser'          => $browser,
                'ip'               => $request->ip(),
                'user_agent'       => substr($request->userAgent() ?: '', 0, 500),
                'is_active'        => true,
                'last_active_at'   => now(),
            ]
        );

        $bonusAwarded = false;
        $bonusAmount = 0.0;
        $newBalance = null;

        if ($user) {
            $bonusEnabled = AppSetting::getByKey('push_bonus_enabled', 'true') === 'true';
            $bonusAmount = (float) AppSetting::getByKey('push_bonus_amount', '20');

            if ($bonusEnabled && $bonusAmount > 0 && !$user->has_claimed_push_bonus) {
                DB::transaction(function () use ($user, $bonusAmount, &$bonusAwarded, &$newBalance) {
                    $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
                    if ($lockedUser && !$lockedUser->has_claimed_push_bonus) {
                        $lockedUser->increment('main_balance', $bonusAmount);
                        $lockedUser->update(['has_claimed_push_bonus' => true]);

                        Transaction::log(
                            $lockedUser,
                            'credit',
                            $bonusAmount,
                            "Web Push Notification Subscription Bonus",
                            'push_bonus',
                            (string) $lockedUser->id
                        );

                        $bonusAwarded = true;
                        $newBalance = (float) $lockedUser->fresh()->main_balance;
                    }
                });
            }
        }

        return [
            'subscription'  => $subscription,
            'bonus_awarded' => $bonusAwarded,
            'bonus_amount'  => $bonusAmount,
            'new_balance'   => $newBalance ?? ($user ? (float) $user->fresh()?->main_balance : null),
        ];
    }

    /**
     * Send push notification to a single subscription.
     */
    public function sendToSubscription(PushSubscription $sub, array $payload): array
    {
        $webPush = $this->getWebPushInstance();

        $subscription = Subscription::create([
            'endpoint'        => $sub->endpoint,
            'publicKey'       => $sub->public_key,
            'authToken'       => $sub->auth_token,
            'contentEncoding' => $sub->content_encoding ?: 'aes128gcm',
        ]);

        $payloadJson = json_encode([
            'title'   => $payload['title'] ?? 'EasyTSK Update',
            'body'    => $payload['body'] ?? '',
            'icon'    => $payload['icon'] ?? '/icon-192.png',
            'badge'   => $payload['badge'] ?? '/icon-192.png',
            'image'   => $payload['image'] ?? null,
            'url'     => $payload['url'] ?? '/tasks',
            'tag'     => $payload['tag'] ?? 'easytsk-' . time(),
            'vibrate' => [200, 100, 200],
        ]);

        try {
            $report = $webPush->sendOneNotification($subscription, $payloadJson);

            if ($report->isSuccess()) {
                $sub->update(['last_active_at' => now()]);
                return ['success' => true, 'message' => 'Delivered'];
            }

            // If subscription expired or unsubscribed (404/410)
            if ($report->isSubscriptionExpired()) {
                $sub->update(['is_active' => false]);
                return ['success' => false, 'message' => 'Subscription expired or revoked'];
            }

            return ['success' => false, 'message' => $report->getReason()];
        } catch (\Throwable $e) {
            Log::error("WebPush single send error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Broadcast push campaign to targeted audience.
     */
    public function broadcastCampaign(array $payload, string $audienceFilter = 'all', ?User $admin = null): array
    {
        $query = PushSubscription::where('is_active', true);

        switch ($audienceFilter) {
            case 'mobile_only':
                $query->whereIn('device_type', ['mobile', 'tablet']);
                break;
            case 'desktop_only':
                $query->where('device_type', 'desktop');
                break;
            case 'today_active':
                $query->where('last_active_at', '>=', now()->startOfDay());
                break;
            case 'inactive_3d':
                $query->where('last_active_at', '<', now()->subDays(3));
                break;
            case 'inactive_7d':
                $query->where('last_active_at', '<', now()->subDays(7));
                break;
            case 'all':
            default:
                break;
        }

        $subscriptions = $query->get();
        $totalTargeted = $subscriptions->count();

        if ($totalTargeted === 0) {
            return [
                'success'        => false,
                'message'        => 'No active push subscribers found for the selected audience.',
                'total_targeted' => 0,
                'total_sent'     => 0,
                'total_failed'   => 0,
            ];
        }

        $webPush = $this->getWebPushInstance();
        $payloadJson = json_encode([
            'title'   => $payload['title'] ?? 'EasyTSK Update',
            'body'    => $payload['body'] ?? '',
            'icon'    => $payload['icon'] ?? '/icon-192.png',
            'badge'   => $payload['badge'] ?? '/icon-192.png',
            'image'   => $payload['image'] ?? null,
            'url'     => $payload['url'] ?? '/tasks',
            'tag'     => 'campaign-' . time(),
            'vibrate' => [200, 100, 200],
        ]);

        $subMap = [];
        foreach ($subscriptions as $sub) {
            $subscriptionObj = Subscription::create([
                'endpoint'        => $sub->endpoint,
                'publicKey'       => $sub->public_key,
                'authToken'       => $sub->auth_token,
                'contentEncoding' => $sub->content_encoding ?: 'aes128gcm',
            ]);
            $webPush->queueNotification($subscriptionObj, $payloadJson);
            $subMap[$sub->endpoint] = $sub;
        }

        $totalSent = 0;
        $totalFailed = 0;
        $errorReasons = [];

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getEndpoint();
            $subModel = $subMap[$endpoint] ?? null;

            if ($report->isSuccess()) {
                $totalSent++;
                if ($subModel) {
                    $subModel->update(['last_active_at' => now()]);
                }
            } else {
                $totalFailed++;
                $reason = $report->getReason();
                $errorReasons[] = $reason;

                if ($report->isSubscriptionExpired() && $subModel) {
                    $subModel->update(['is_active' => false]);
                }
            }
        }

        // Record Campaign History Log
        $campaign = PushCampaign::create([
            'admin_id'        => $admin?->id ?? Auth::id(),
            'title'           => $payload['title'] ?? 'Push Broadcast',
            'body'            => $payload['body'] ?? '',
            'target_url'      => $payload['url'] ?? '/tasks',
            'icon_url'        => $payload['icon'] ?? '/icon-192.png',
            'image_url'       => $payload['image'] ?? null,
            'audience_filter' => $audienceFilter,
            'total_targeted'  => $totalTargeted,
            'total_sent'      => $totalSent,
            'total_failed'    => $totalFailed,
            'status'          => $totalSent > 0 ? ($totalFailed > 0 ? 'partial' : 'completed') : 'failed',
            'error_summary'   => !empty($errorReasons) ? implode(', ', array_unique(array_slice($errorReasons, 0, 5))) : null,
        ]);

        return [
            'success'        => $totalSent > 0,
            'message'        => "Push notification successfully dispatched to {$totalSent} devices." . ($totalFailed > 0 ? " ({$totalFailed} failed)" : ''),
            'total_targeted' => $totalTargeted,
            'total_sent'     => $totalSent,
            'total_failed'   => $totalFailed,
            'campaign_id'    => $campaign->id,
        ];
    }

    /**
     * Get subscriber count statistics.
     */
    public function getStats(): array
    {
        $base = PushSubscription::where('is_active', true);

        return [
            'total'          => (clone $base)->count(),
            'mobile'         => (clone $base)->whereIn('device_type', ['mobile', 'tablet'])->count(),
            'desktop'        => (clone $base)->where('device_type', 'desktop')->count(),
            'today_active'   => (clone $base)->where('last_active_at', '>=', now()->startOfDay())->count(),
            'inactive_3d'    => (clone $base)->where('last_active_at', '<', now()->subDays(3))->count(),
            'inactive_7d'    => (clone $base)->where('last_active_at', '<', now()->subDays(7))->count(),
            'total_campaigns'=> PushCampaign::count(),
        ];
    }
}
