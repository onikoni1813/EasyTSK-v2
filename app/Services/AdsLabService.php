<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Offerwall;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdsLabService
{
    protected ?Offerwall $offerwall;
    protected ?string $appId = null;
    protected ?string $secretKey = null;
    protected float $rewardRatio = 1.0;

    protected string $primaryBaseUrl = 'https://adslab.me/api/tasks-share';

    public function __construct(?Offerwall $offerwall = null)
    {
        $this->offerwall = $offerwall ?: Offerwall::where(function ($q) {
            $q->where('name', 'AdsLab')
              ->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(name, ' ', ''), '-', ''), '_', '')) = 'adslab'");
        })->first();

        $this->resolveCredentials();
    }

    /**
     * Resolve API credentials from Offerwall model, AppSetting.
     */
    protected function resolveCredentials(): void
    {
        if ($this->offerwall) {
            $this->appId = $this->offerwall->app_id;
            $this->secretKey = $this->offerwall->secret_key;
            $this->rewardRatio = (float) ($this->offerwall->reward_ratio ?: 1.0);

            // Fallback: parse query parameters from iframe_url_pattern if fields are empty
            if ((empty($this->appId) || empty($this->secretKey)) && !empty($this->offerwall->iframe_url_pattern)) {
                $parsed = parse_url($this->offerwall->iframe_url_pattern);
                if (!empty($parsed['query'])) {
                    parse_str($parsed['query'], $queryParams);
                    $this->appId = $this->appId ?: ($queryParams['app_id'] ?? $queryParams['appId'] ?? null);
                    $this->secretKey = $this->secretKey ?: ($queryParams['secret_key'] ?? $queryParams['secretKey'] ?? null);
                }
            }
        }
    }

    /**
     * Check if AdsLab API has all required credentials configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->appId) && !empty($this->secretKey);
    }

    public function getOfferwall(): ?Offerwall
    {
        return $this->offerwall;
    }

    public function getAppId(): ?string
    {
        return $this->appId;
    }

    public function getSecretKey(): ?string
    {
        return $this->secretKey;
    }

    public function getRewardRatio(): float
    {
        return $this->rewardRatio;
    }

    /**
     * Detect device OS from user agent.
     */
    public function detectDeviceOs(Request $request): string
    {
        $ua = strtolower($request->userAgent() ?: '');
        if (str_contains($ua, 'android')) {
            return 'android';
        }
        if (str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ipod') || str_contains($ua, 'ios')) {
            return 'ios';
        }
        if (str_contains($ua, 'windows')) {
            return 'windows';
        }
        if (str_contains($ua, 'macintosh') || str_contains($ua, 'mac os')) {
            return 'macos';
        }
        if (str_contains($ua, 'linux')) {
            return 'linux';
        }
        return 'all';
    }

    /**
     * Detect device type (mobile, tablet, desktop).
     */
    public function detectDeviceType(Request $request): string
    {
        $ua = strtolower($request->userAgent() ?: '');
        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            return 'tablet';
        }
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }
        return 'desktop';
    }

    /**
     * Detect country code from headers or fallback.
     */
    public function detectCountryCode(Request $request): string
    {
        $country = $request->header('CF-IPCountry') 
            ?? $request->header('X-Country-Code') 
            ?? $request->header('GEOIP_COUNTRY_CODE')
            ?? $request->server('HTTP_CF_IPCOUNTRY')
            ?? 'BD';

        return strtoupper(trim((string) $country)) ?: 'BD';
    }

    /**
     * Calculate points from USD payout based on platform conversion rate and offerwall ratio.
     */
    public function calculatePoints(float $payoutUsd): float
    {
        $conversionRate = (float) AppSetting::getByKey('conversion_rate', 100);
        $multiplier = (float) AppSetting::rewardMultiplier();
        $ratio = $this->rewardRatio ?: 1.0;

        $coins = $payoutUsd * $ratio * $conversionRate * $multiplier;
        return round($coins, 2);
    }

    /**
     * Fetch offers from AdsLab API with caching and error fallback.
     *
     * @param User $user Current authenticated user
     * @param Request $request Current HTTP request for context
     * @param bool $forceRefresh Bypass cache if true
     * @return array [success => bool, offers => array, message => string, is_cached => bool]
     */
    public function getOffers(User $user, Request $request, bool $forceRefresh = false): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'offers'  => [],
                'message' => 'AdsLab API credentials (app_id, secret_key) are not fully configured in Offerwalls settings.',
                'is_configured' => false,
            ];
        }

        $deviceOs = $request->query('device_os') ?: $this->detectDeviceOs($request);
        $deviceType = $request->query('device_type') ?: $this->detectDeviceType($request);
        $countryCode = $request->query('country') ?: $this->detectCountryCode($request);
        $clientIp = $request->ip() ?: '127.0.0.1';

        $cacheKey = "adslab_offers_{$this->appId}_{$countryCode}_{$deviceOs}_{$deviceType}_u{$user->id}";

        if (!$forceRefresh && Cache::has($cacheKey)) {
            $cachedOffers = Cache::get($cacheKey);
            return [
                'success'       => true,
                'offers'        => $cachedOffers,
                'message'       => 'Offers loaded from cache',
                'is_cached'     => true,
                'is_configured' => true,
                'country'       => $countryCode,
                'device_os'     => $deviceOs,
            ];
        }

        $offers = $this->fetchOffersApi($user->id, $clientIp, $countryCode);

        if (!empty($offers)) {
            // Cache successful offer list for 5 minutes (300 seconds)
            Cache::put($cacheKey, $offers, 300);

            return [
                'success'       => true,
                'offers'        => $offers,
                'message'       => 'Offers fetched successfully',
                'is_cached'     => false,
                'is_configured' => true,
                'country'       => $countryCode,
                'device_os'     => $deviceOs,
            ];
        }

        return [
            'success'       => true,
            'offers'        => [],
            'message'       => 'No offers currently available for your region and device.',
            'is_cached'     => false,
            'is_configured' => true,
            'country'       => $countryCode,
            'device_os'     => $deviceOs,
        ];
    }

    /**
     * Fetch offers from AdsLab API
     */
    protected function fetchOffersApi(int|string $userId, string $ip, string $countryCode): array
    {
        // URL Format: https://adslab.me/api/tasks-share/{app_id}/{secret_key}/{country-code}/{user-id}/{user-ip}/offers
        $endpoint = "{$this->primaryBaseUrl}/{$this->appId}/{$this->secretKey}/{$countryCode}/{$userId}/{$ip}/offers";

        try {
            $response = Http::timeout(10)->get($endpoint);
            if ($response->successful()) {
                $json = $response->json();
                return $this->formatOffersData($json, $userId, $countryCode);
            }
            Log::warning("AdsLabService endpoint returned status {$response->status()}: " . $response->body());
        } catch (\Throwable $e) {
            Log::error("AdsLabService endpoint exception: " . $e->getMessage());
        }

        return [];
    }


    /**
     * Format and normalize raw AdsLab response into consistent EasyTSK offer items.
     */
    protected function formatOffersData(mixed $rawJson, int|string $userId, ?string $targetCountry = null): array
    {
        if (!is_array($rawJson)) {
            return [];
        }

        $items = $rawJson;

        if (empty($items)) {
            return [];
        }

        $formatted = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $offerId = (string) ($item['id'] ?? $item['_id'] ?? '');
            if ($offerId === '') {
                continue;
            }

            $name = (string) ($item['name'] ?? $item['title'] ?? 'AdsLab Offer');
            $payoutUsd = (float) ($item['reward'] ?? $item['reward_usd'] ?? 0);
            $rewardCoins = $this->calculatePoints($payoutUsd);

            $imageUrl = (string) ($item['image'] ?? $item['icon'] ?? '');
            $clickUrl = (string) ($item['url'] ?? '');

            // Ensure user ID macro is replaced or appended in tracking click URL
            // According to Docs: "You must redirect the user to this exact URL when they click the task."
            // But we should just make sure user_id is there just in case, though adslab API might pre-fill it.
            if ($clickUrl !== '') {
                if (str_contains($clickUrl, '{user_id}') || str_contains($clickUrl, '[user_id]')) {
                    $clickUrl = str_replace(['{user_id}', '[user_id]'], (string) $userId, $clickUrl);
                } elseif (!str_contains($clickUrl, 'user_id=') && !str_contains($clickUrl, 'uid=') && !str_contains($clickUrl, 'sub1=')) {
                    $separator = str_contains($clickUrl, '?') ? '&' : '?';
                    $clickUrl .= "{$separator}sub1={$userId}";
                }
            }

            // Extract categories
            $categories = [];
            if (isset($item['categories']) && is_array($item['categories'])) {
                $categories = array_values(array_filter(array_map('trim', $item['categories'])));
            }

            // Extract instructions / steps
            $instructions = [];
            if (!empty($item['description']) && is_string($item['description'])) {
                $instructions[] = trim($item['description']);
            }
            if (isset($item['goals']) && is_array($item['goals'])) {
                foreach ($item['goals'] as $goal) {
                    if (isset($goal['title'])) {
                        $instructions[] = trim($goal['title']);
                    }
                }
            }

            $osList = $item['os'] ?? 'all';
            $deviceOs = is_array($osList) ? implode(',', $osList) : (string) $osList;
            
            // Format to generic device type
            $deviceType = 'all';
            if (str_contains(strtolower($deviceOs), 'android') || str_contains(strtolower($deviceOs), 'ios')) {
                $deviceType = 'mobile';
            } elseif (str_contains(strtolower($deviceOs), 'windows') || str_contains(strtolower($deviceOs), 'mac')) {
                $deviceType = 'desktop';
            }

            $formatted[] = [
                'id'            => $offerId,
                'name'          => $name,
                'image_url'     => $imageUrl,
                'click_url'     => $clickUrl,
                'payout_usd'    => $payoutUsd,
                'reward_coins'  => $rewardCoins,
                'categories'    => $categories,
                'instructions'  => array_unique($instructions),
                'short_desc'    => $instructions[0] ?? 'Complete this offer to earn rewards.',
                'device_os'     => $deviceOs,
                'device_type'   => $deviceType,
            ];
        }

        return $formatted;
    }
}

