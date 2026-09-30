<?php

namespace App\Services;

use App\Models\ShortlinkProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShortlinkService
{
    /**
     * Generate a shortened URL from any of the supported shortlink provider APIs.
     *
     * @param string $apiEndpoint
     * @param string $apiKey
     * @param string $destinationUrl
     * @param string|null $providerName
     * @return array{success: bool, shortened_url: ?string, message: ?string}
     */
    public function generateShortlink(
        string $apiEndpoint,
        string $apiKey,
        string $destinationUrl,
        ?string $providerName = null
    ): array {
        $apiEndpoint = trim($apiEndpoint);
        $apiKey = trim($apiKey);
        $destinationUrl = trim($destinationUrl);

        if (empty($apiKey)) {
            return [
                'success' => false,
                'shortened_url' => null,
                'message' => 'API Key ফিল্ডটি ফাঁকা! অনুগ্রহ করে Provider এডিট করে API Key সংরক্ষণ করুন।',
            ];
        }

        if (empty($apiEndpoint)) {
            return [
                'success' => false,
                'shortened_url' => null,
                'message' => 'API Endpoint URL প্রদান করা হয়নি!',
            ];
        }

        $driver = $this->resolveDriver($apiEndpoint, $providerName);

        try {
            $startTime = microtime(true);
            $response = null;

            if ($driver === 'adfocus') {
                // AdFoc.us uses key= and url= parameters.
                // NOTE: AdFoc.us API rejects standard URL-encoded parameters (e.g. %3A%2F%2F) and expects the raw URL string.
                $cleanEndpoint = rtrim($apiEndpoint, '/?& ');
                $requestUrl = "{$cleanEndpoint}/?key={$apiKey}&url={$destinationUrl}";

                $response = Http::timeout(10)
                    ->withoutVerifying()
                    ->withHeaders(['User-Agent' => 'EasyTSK/2.0'])
                    ->get($requestUrl);
            } elseif ($driver === 'shrtfly') {
                // ShrtFly uses api=, url=, type=1, format=json
                $response = Http::timeout(10)
                    ->withoutVerifying()
                    ->withHeaders(['User-Agent' => 'EasyTSK/2.0'])
                    ->get($apiEndpoint, [
                        'api' => $apiKey,
                        'url' => $destinationUrl,
                        'type' => 1,
                        'format' => 'json',
                    ]);
            } elseif ($driver === 'admaven') {
                // Ad-Maven Content Locker API requires Authorization Bearer & X-API-KEY headers
                $cleanKey = trim($apiKey);
                $bearerToken = str_starts_with($cleanKey, 'Bearer ') ? trim(substr($cleanKey, 7)) : $cleanKey;

                $headers = [
                    'User-Agent'    => 'EasyTSK/2.0',
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $bearerToken,
                    'X-API-KEY'     => $bearerToken,
                    'api-key'       => $bearerToken,
                ];

                $cleanTitle = !empty($providerName) ? $providerName : 'EasyTSK Task';

                $payload = [
                    'title'           => $cleanTitle,
                    'name'            => $cleanTitle,
                    'url'             => $destinationUrl,
                    'destination_url' => $destinationUrl,
                    'link'            => $destinationUrl,
                    'target_url'      => $destinationUrl,
                    'description'     => 'Complete verification to continue',
                    'api_key'         => $bearerToken,
                    'api'             => $bearerToken,
                ];

                // Attempt 1: JSON POST with Bearer and X-API-KEY headers
                $response = Http::timeout(10)
                    ->withoutVerifying()
                    ->withHeaders($headers)
                    ->post($apiEndpoint, $payload);

                // Attempt 2: Form POST if JSON POST failed
                if (!$response || !$response->successful()) {
                    $response = Http::timeout(10)
                        ->withoutVerifying()
                        ->withHeaders([
                            'User-Agent'    => 'EasyTSK/2.0',
                            'Accept'        => 'application/json',
                            'Authorization' => 'Bearer ' . $bearerToken,
                            'X-API-KEY'     => $bearerToken,
                        ])
                        ->asForm()
                        ->post($apiEndpoint, $payload);
                }

                // Attempt 3: GET query if POST failed
                if (!$response || !$response->successful()) {
                    $response = Http::timeout(10)
                        ->withoutVerifying()
                        ->withHeaders([
                            'User-Agent'    => 'EasyTSK/2.0',
                            'Accept'        => 'application/json',
                            'Authorization' => 'Bearer ' . $bearerToken,
                            'X-API-KEY'     => $bearerToken,
                        ])
                        ->get($apiEndpoint, [
                            'title'           => $cleanTitle,
                            'name'            => $cleanTitle,
                            'api_key'         => $bearerToken,
                            'api'             => $bearerToken,
                            'url'             => $destinationUrl,
                            'destination_url' => $destinationUrl,
                        ]);
                }
            } else {
                // Standard AdLinkFly & generic API shorteners (ShrinkMe, Exe, GPLinks, Droplink, Cuty, ClkSh, CutWin, FcLc, KutLi, ShrinkEarn, etc.)
                $response = Http::timeout(10)
                    ->withoutVerifying()
                    ->withHeaders(['User-Agent' => 'EasyTSK/2.0'])
                    ->get($apiEndpoint, [
                        'api' => $apiKey,
                        'url' => $destinationUrl,
                    ]);
            }

            if (!$response || !$response->successful()) {
                $status = $response ? $response->status() : 'Unknown';
                $body = $response ? $response->body() : 'No response';
                Log::warning("Shortlink API HTTP {$status} error from {$apiEndpoint}: {$body}");

                // Check if response has JSON error message
                $json = $response ? $response->json() : null;
                $errMsg = $json['message'] ?? (is_string($json['result'] ?? null) ? $json['result'] : null) ?? null;

                if ($status == 401 || $errMsg === 'Unauthorized') {
                    if ($driver === 'admaven') {
                        $errMsg = 'AdMaven Unauthorized (401): API Key সঠিক নয় অথবা AdMaven একাউন্টের প্রোফাইল তথ্য (Payment method, address, domain) অসম্পূর্ণ। AdMaven ড্যাশবোর্ডে গিয়ে "New Content locker" -> "Key Generator" থেকে Key তৈরি করে সেভ করুন।';
                    } else {
                        $errMsg = 'Unauthorized (401): API Key সঠিক নয় অথবা প্রোভাইডারে রিকোয়েস্ট অনুমোদিত নয়।';
                    }
                } elseif (empty($errMsg)) {
                    $errMsg = "Provider returned HTTP {$status}.";
                }

                return [
                    'success' => false,
                    'shortened_url' => null,
                    'message' => $errMsg,
                ];
            }

            $body = trim($response->body());
            $shortenedUrl = $this->parseResponse($driver, $response);

            // Robust regex fallback if JSON structure was unusual or raw string returned
            if (empty($shortenedUrl) && !empty($body)) {
                if (preg_match('/"(?:full_short|fullShort|locker_url|shortened_url|content_locker_url)"\s*:\s*"([^"]+)"/i', $body, $m)) {
                    $shortenedUrl = $this->sanitizeUrl($m[1]);
                }
            }

            if (!empty($shortenedUrl) && filter_var($shortenedUrl, FILTER_VALIDATE_URL)) {
                return [
                    'success' => true,
                    'shortened_url' => $shortenedUrl,
                    'message' => null,
                ];
            }

            // If parsing failed or invalid URL returned
            $json = $response->json();
            $errMsg = 'Could not retrieve shortened URL from provider.';
            if (is_array($json)) {
                if (!empty($json['message'])) {
                    $errMsg = $json['message'];
                } elseif (!empty($json['result']) && is_string($json['result'])) {
                    $errMsg = $json['result'];
                }
            } elseif ($body === '0' || $body === 'error') {
                $errMsg = 'Provider rejected the request or API key is invalid.';
            }

            return [
                'success' => false,
                'shortened_url' => null,
                'message' => $errMsg,
            ];
        } catch (\Throwable $e) {
            Log::error("Shortlink API exception for {$apiEndpoint}: " . $e->getMessage());
            return [
                'success' => false,
                'shortened_url' => null,
                'message' => 'Connection to shortlink provider timed out or failed. Please try again.',
            ];
        }
    }

    /**
     * Test a provider's credentials directly.
     *
     * @param ShortlinkProvider $provider
     * @param string|null $testDestination
     * @return array{success: bool, shortened_url: ?string, message: ?string, latency_ms: int}
     */
    public function testProvider(ShortlinkProvider $provider, ?string $testDestination = null): array
    {
        if (empty(trim($provider->api_key ?? ''))) {
            return [
                'success' => false,
                'shortened_url' => null,
                'message' => '⚠️ API Key ফিল্ডটি ফাঁকা! অনুগ্রহ করে Edit বাটনে ক্লিক করে আপনার ' . $provider->name . ' একাউন্ট থেকে API Key কপি করে সেভ করুন।',
                'latency_ms' => 0,
            ];
        }

        if (!$testDestination) {
            $currentUrl = url('/');
            // If the local environment or current host is localhost / 127.0.0.1, fallback to a public domain
            // so external shorteners (like AdFoc.us) won't reject it as a private/internal destination.
            if (str_contains($currentUrl, 'localhost') || str_contains($currentUrl, '127.0.0.1') || !filter_var($currentUrl, FILTER_VALIDATE_URL)) {
                $testDestination = 'https://google.com';
            } else {
                $testDestination = $currentUrl;
            }
        }
        $start = microtime(true);

        $result = $this->generateShortlink(
            $provider->api_url,
            $provider->api_key,
            $testDestination,
            $provider->name
        );

        $latency = (int) round((microtime(true) - $start) * 1000);
        $result['latency_ms'] = $latency;

        return $result;
    }

    /**
     * Resolve the driver type (adfocus, shrtfly, adlinkfly) based on endpoint or provider name.
     */
    protected function resolveDriver(string $apiEndpoint, ?string $providerName = null): string
    {
        $combined = strtolower($apiEndpoint . ' ' . ($providerName ?? ''));

        if (str_contains($combined, 'adfoc.us') || str_contains($combined, 'adfocus')) {
            return 'adfocus';
        }

        if (str_contains($combined, 'shrtfly')) {
            return 'shrtfly';
        }

        if (str_contains($combined, 'ad-maven') || str_contains($combined, 'admaven')) {
            return 'admaven';
        }

        return 'adlinkfly';
    }

    /**
     * Parse the response depending on the provider driver.
     */
    protected function parseResponse(string $driver, $response): ?string
    {
        $body = trim($response->body());

        if ($driver === 'adfocus') {
            // AdFoc.us returns plain text URL or 0 on error
            if ($body !== '0' && str_starts_with($body, 'http')) {
                return $this->sanitizeUrl($body);
            }
            return null;
        }

        $json = $response->json();

        if (is_array($json)) {
            // Flatten / unpack list if response is an array of items (e.g. Ad-Maven returns [ {"short": "...", "full_short": "..."} ])
            $candidates = [];
            if (isset($json[0]) && is_array($json[0])) {
                $candidates[] = $json[0];
            }
            if (isset($json['data']) && is_array($json['data'])) {
                if (isset($json['data'][0]) && is_array($json['data'][0])) {
                    $candidates[] = $json['data'][0];
                }
                $candidates[] = $json['data'];
            }
            $candidates[] = $json;

            foreach ($candidates as $item) {
                // 1. AdMaven full_short field
                if (!empty($item['full_short']) && filter_var($item['full_short'], FILTER_VALIDATE_URL)) {
                    return $this->sanitizeUrl($item['full_short']);
                }
                if (!empty($item['fullShort']) && filter_var($item['fullShort'], FILTER_VALIDATE_URL)) {
                    return $this->sanitizeUrl($item['fullShort']);
                }

                // 2. Standard AdLinkFly field
                if (!empty($item['shortenedUrl'])) {
                    return $this->sanitizeUrl($item['shortenedUrl']);
                }

                // 3. ShrtFly field
                if (!empty($item['result']['shorten_url'])) {
                    return $this->sanitizeUrl($item['result']['shorten_url']);
                }

                // 4. Content Locker & shortened fields
                if (!empty($item['shortened_url'])) {
                    return $this->sanitizeUrl($item['shortened_url']);
                }
                if (!empty($item['locker_url'])) {
                    return $this->sanitizeUrl($item['locker_url']);
                }
                if (!empty($item['lockerUrl'])) {
                    return $this->sanitizeUrl($item['lockerUrl']);
                }
                if (!empty($item['content_locker_url'])) {
                    return $this->sanitizeUrl($item['content_locker_url']);
                }
                if (!empty($item['contentLockerUrl'])) {
                    return $this->sanitizeUrl($item['contentLockerUrl']);
                }
                if (!empty($item['link']) && filter_var($item['link'], FILTER_VALIDATE_URL)) {
                    return $this->sanitizeUrl($item['link']);
                }
                if (!empty($item['short_url']) && filter_var($item['short_url'], FILTER_VALIDATE_URL)) {
                    return $this->sanitizeUrl($item['short_url']);
                }
                if (!empty($item['url']) && filter_var($item['url'], FILTER_VALIDATE_URL)) {
                    return $this->sanitizeUrl($item['url']);
                }
                if (!empty($item['short']) && filter_var($item['short'], FILTER_VALIDATE_URL)) {
                    return $this->sanitizeUrl($item['short']);
                }
            }

            // Direct string URL in data or result
            if (isset($json['data']) && is_string($json['data']) && (str_starts_with($json['data'], 'http://') || str_starts_with($json['data'], 'https://'))) {
                return $this->sanitizeUrl($json['data']);
            }
            if (isset($json['result']) && is_string($json['result']) && (str_starts_with($json['result'], 'http://') || str_starts_with($json['result'], 'https://'))) {
                return $this->sanitizeUrl($json['result']);
            }
        }

        // Plain text fallback if body is a valid URL
        if (str_starts_with($body, 'http://') || str_starts_with($body, 'https://')) {
            return $this->sanitizeUrl($body);
        }

        return null;
    }

    /**
     * Clean and sanitize URL string. Handles double quotes and escaped slashes.
     */
    protected function sanitizeUrl(string $url): string
    {
        // Strip escaped slashes
        $url = stripslashes($url);
        // Strip wrapping quotes or whitespace
        $url = trim($url, " \t\n\r\0\x0B\"'");

        return $url;
    }
}
