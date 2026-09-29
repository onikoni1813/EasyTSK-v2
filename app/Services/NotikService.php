<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Offerwall;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotikService
{
    protected ?Offerwall $offerwall;
    protected ?string $apiKey = null;
    protected ?string $pubId = null;
    protected ?string $appId = null;
    protected ?string $secretKey = null;
    protected float $rewardRatio = 1.0;

    protected string $primaryBaseUrl = 'https://notik.me/api';

    public function __construct(?Offerwall $offerwall = null)
    {
        $this->offerwall = $offerwall ?: Offerwall::where(function ($q) {
            $q->where('name', 'Notik')
              ->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(name, ' ', ''), '-', ''), '_', '')) = 'notik'");
        })->first();

        $this->resolveCredentials();
    }

    /**
     * Resolve API credentials from Offerwall model, AppSetting, or iframe_url_pattern fallback.
     */
    protected function resolveCredentials(): void
    {
        if ($this->offerwall) {
            $this->apiKey = $this->offerwall->api_key;
            $this->pubId = $this->offerwall->pub_id;
            $this->appId = $this->offerwall->app_id;
            $this->secretKey = $this->offerwall->secret_key;
            $this->rewardRatio = (float) ($this->offerwall->reward_ratio ?: 1.0);

            // Fallback: parse query parameters from iframe_url_pattern if fields are empty
            if ((empty($this->apiKey) || empty($this->pubId) || empty($this->appId)) && !empty($this->offerwall->iframe_url_pattern)) {
                $parsed = parse_url($this->offerwall->iframe_url_pattern);
                if (!empty($parsed['query'])) {
                    parse_str($parsed['query'], $queryParams);
                    $this->apiKey = $this->apiKey ?: ($queryParams['api_key'] ?? $queryParams['apikey'] ?? null);
                    $this->pubId = $this->pubId ?: ($queryParams['pub_id'] ?? $queryParams['pubId'] ?? null);
                    $this->appId = $this->appId ?: ($queryParams['app_id'] ?? $queryParams['appId'] ?? null);
                }
            }
        }

        // Secondary fallback: AppSetting
        $this->apiKey = $this->apiKey ?: AppSetting::getByKey('notik_api_key', config('services.notik.api_key'));
        $this->pubId = $this->pubId ?: AppSetting::getByKey('notik_pub_id', config('services.notik.pub_id'));
        $this->appId = $this->appId ?: AppSetting::getByKey('notik_app_id', config('services.notik.app_id'));
        $this->secretKey = $this->secretKey ?: AppSetting::getByKey('notik_secret_key', config('services.notik.secret_key'));
    }

    /**
     * Check if Notik API has all required credentials configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->pubId) && !empty($this->appId);
    }

    public function getOfferwall(): ?Offerwall
    {
        return $this->offerwall;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function getPubId(): ?string
    {
        return $this->pubId;
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
     * Fetch offers from Notik API with caching and error fallback.
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
                'message' => 'Notik API credentials (api_key, pub_id, app_id) are not fully configured in Offerwalls settings.',
                'is_configured' => false,
            ];
        }

        $deviceOs = $request->query('device_os') ?: $this->detectDeviceOs($request);
        $deviceType = $request->query('device_type') ?: $this->detectDeviceType($request);
        $countryCode = $request->query('country') ?: $this->detectCountryCode($request);
        $clientIp = $request->ip() ?: '127.0.0.1';

        $cacheKey = "notik_offers_{$this->pubId}_{$this->appId}_{$countryCode}_{$deviceOs}_{$deviceType}_u{$user->id}";

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

        // Try Filtered Offers Endpoint first (v1)
        $offers = $this->fetchFilteredOffers($user->id, $clientIp, $request->userAgent() ?: '', $deviceOs, $deviceType, $countryCode);

        // Fallback to All Offers (v2) if filtered returns nothing
        if (empty($offers)) {
            $offers = $this->fetchAllOffers($user->id, $countryCode);
        }

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
     * Fetch filtered offers from /api/v1/get-offers/filtered
     */
    protected function fetchFilteredOffers(int|string $userId, string $ip, string $ua, string $os, string $type, string $country): array
    {
        $endpoint = "{$this->primaryBaseUrl}/v1/get-offers/filtered";
        $params = [
            'api_key'      => $this->apiKey,
            'pub_id'       => $this->pubId,
            'app_id'       => $this->appId,
            'user_id'      => (string) $userId,
            'ip'           => $ip,
            'user_agent'   => $ua,
            'device_os'    => $os,
            'device_type'  => $type,
            'country_code' => $country,
        ];

        try {
            $response = Http::timeout(10)->get($endpoint, $params);
            if ($response->successful()) {
                $json = $response->json();
                return $this->formatOffersData($json, $userId);
            }
            Log::warning("NotikService filtered endpoint returned status {$response->status()}: " . $response->body());
        } catch (\Throwable $e) {
            Log::error("NotikService filtered endpoint exception: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Fetch all offers fallback from /api/v2/get-offers/all
     */
    protected function fetchAllOffers(int|string $userId, ?string $targetCountry = null): array
    {
        $endpoint = "{$this->primaryBaseUrl}/v2/get-offers/all";
        $params = [
            'api_key' => $this->apiKey,
            'pub_id'  => $this->pubId,
            'app_id'  => $this->appId,
        ];

        try {
            $response = Http::timeout(10)->get($endpoint, $params);
            if ($response->successful()) {
                $json = $response->json();
                return $this->formatOffersData($json, $userId, $targetCountry);
            }
            Log::warning("NotikService all-offers endpoint returned status {$response->status()}: " . $response->body());
        } catch (\Throwable $e) {
            Log::error("NotikService all-offers endpoint exception: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Format and normalize raw Notik response into consistent EasyTSK offer items.
     */
    protected function formatOffersData(mixed $rawJson, int|string $userId, ?string $targetCountry = null): array
    {
        if (!is_array($rawJson)) {
            return [];
        }

        // Locate offers array (can be under 'offers.data', 'data', 'offers', or root array)
        $items = [];
        if (isset($rawJson['offers']['data']) && is_array($rawJson['offers']['data'])) {
            $items = $rawJson['offers']['data'];
        } elseif (isset($rawJson['data']) && is_array($rawJson['data'])) {
            $items = $rawJson['data'];
        } elseif (isset($rawJson['offers']) && is_array($rawJson['offers'])) {
            $items = $rawJson['offers'];
        } elseif (isset($rawJson[0]) && is_array($rawJson)) {
            $items = $rawJson;
        }

        if (empty($items)) {
            return [];
        }

        $formatted = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            // Target Country filter (e.g. 'BD')
            if (!empty($targetCountry) && !empty($item['country_code'])) {
                $ccs = is_array($item['country_code']) ? $item['country_code'] : [$item['country_code']];
                $upperCcs = array_map('strtoupper', array_map('strval', $ccs));
                if (!in_array('ALL', $upperCcs) && !in_array(strtoupper($targetCountry), $upperCcs)) {
                    continue;
                }
            }

            $offerId = (string) ($item['offer_id'] ?? $item['id'] ?? $item['campaign_id'] ?? '');
            if ($offerId === '') {
                continue;
            }

            $name = (string) ($item['name'] ?? $item['title'] ?? 'Sponsored Offer');
            $payoutUsd = (float) ($item['payout'] ?? $item['amount'] ?? $item['revenue'] ?? 0);
            $rewardCoins = $this->calculatePoints($payoutUsd);

            $imageUrl = (string) ($item['image_url'] ?? $item['icon_url'] ?? $item['icon'] ?? $item['image'] ?? '');
            $clickUrl = (string) ($item['click_url'] ?? $item['url'] ?? $item['link'] ?? '');

            // Ensure user ID macro is replaced or appended in tracking click URL
            if ($clickUrl !== '') {
                if (str_contains($clickUrl, '{user_id}') || str_contains($clickUrl, '[user_id]')) {
                    $clickUrl = str_replace(['{user_id}', '[user_id]'], (string) $userId, $clickUrl);
                } elseif (!str_contains($clickUrl, 'user_id=') && !str_contains($clickUrl, 'uid=')) {
                    $separator = str_contains($clickUrl, '?') ? '&' : '?';
                    $clickUrl .= "{$separator}user_id={$userId}";
                }
            }

            // Extract categories
            $categories = [];
            if (isset($item['categories']) && is_array($item['categories'])) {
                $categories = array_values(array_filter(array_map('trim', $item['categories'])));
            } elseif (!empty($item['category'])) {
                $categories = [trim((string) $item['category'])];
            }

            // Extract instructions / steps
            $instructions = [];
            foreach (['description1', 'description2', 'description3', 'description', 'requirements'] as $descField) {
                if (!empty($item[$descField]) && is_string($item[$descField])) {
                    $instructions[] = trim($item[$descField]);
                }
            }

            $osList = $item['os'] ?? $item['device_os'] ?? $item['platform'] ?? 'all';
            $deviceOs = is_array($osList) ? implode(',', $osList) : (string) $osList;

            $typeList = $item['devices'] ?? $item['platforms'] ?? $item['device_type'] ?? 'all';
            $deviceType = is_array($typeList) ? implode(',', $typeList) : (string) $typeList;

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
