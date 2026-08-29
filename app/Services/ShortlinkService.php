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

        if (empty($apiEndpoint) || empty($apiKey)) {
            return [
                'success' => false,
                'shortened_url' => null,
                'message' => 'Shortlink provider is missing API endpoint or API key.',
            ];
        }

        $driver = $this->resolveDriver($apiEndpoint, $providerName);

        try {
            $startTime = microtime(true);
            $response = null;

            if ($driver === 'adfocus') {
                // AdFoc.us uses key= and url= parameters
                $response = Http::timeout(10)
                    ->withoutVerifying()
                    ->withHeaders(['User-Agent' => 'EasyTSK/2.0'])
                    ->get($apiEndpoint, [
                        'key' => $apiKey,
                        'url' => $destinationUrl,
                    ]);
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
                $errMsg = $json['message'] ?? (is_string($json['result'] ?? null) ? $json['result'] : null) ?? "Provider returned HTTP {$status}.";

                return [
                    'success' => false,
                    'shortened_url' => null,
                    'message' => $errMsg,
                ];
            }

            $body = trim($response->body());
            $shortenedUrl = $this->parseResponse($driver, $response);

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
        $testDestination = $testDestination ?: url('/');
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
            // 1. Standard AdLinkFly field
            if (!empty($json['shortenedUrl'])) {
                return $this->sanitizeUrl($json['shortenedUrl']);
            }

            // 2. ShrtFly field
            if (!empty($json['result']['shorten_url'])) {
                return $this->sanitizeUrl($json['result']['shorten_url']);
            }

            // 3. Fallback generic fields
            if (!empty($json['url'])) {
                return $this->sanitizeUrl($json['url']);
            }
            if (!empty($json['short'])) {
                return $this->sanitizeUrl($json['short']);
            }
            if (!empty($json['short_url'])) {
                return $this->sanitizeUrl($json['short_url']);
            }
            if (!empty($json['data']['url'])) {
                return $this->sanitizeUrl($json['data']['url']);
            }
            if (!empty($json['data']['shortenedUrl'])) {
                return $this->sanitizeUrl($json['data']['shortenedUrl']);
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
