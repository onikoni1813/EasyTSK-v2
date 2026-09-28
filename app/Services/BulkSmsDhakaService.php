<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BulkSmsDhakaService
{
    protected ?string $apiKey = null;
    protected string $senderId = '1234';

    // The active API base URLs for BulkSMS Dhaka (primary is .net, fallback is .com)
    protected string $primaryBaseUrl = 'https://bulksmsdhaka.net/api';
    protected string $fallbackBaseUrl = 'https://bulksmsdhaka.com/api';

    public function __construct(?string $apiKey = null, ?string $senderId = null)
    {
        $this->apiKey = $apiKey 
            ?? AppSetting::getByKey('bulksmsdhaka_api_key') 
            ?? config('services.bulksmsdhaka.api_key') 
            ?? env('BULKSMSDHAKA_API_KEY');

        $this->senderId = $senderId 
            ?? AppSetting::getByKey('bulksmsdhaka_sender_id', '1234');
    }

    /**
     * Check if the SMS service has an API key configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get the configured API key.
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    /**
     * Get the configured Sender ID / Caller ID.
     */
    public function getSenderId(): string
    {
        return $this->senderId ?: '1234';
    }

    /**
     * Normalize Bangladeshi mobile numbers to standard 11 digits (01XXXXXXXXX).
     */
    public static function formatNumber(string $number): string
    {
        // Remove all non-digit characters
        $digits = preg_replace('/\D/', '', $number);

        // Remove leading 880 if present (e.g. 88017... -> 017...)
        if (str_starts_with($digits, '8801')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Check SMS account balance from BulkSMS Dhaka gateway.
     *
     * @return array [success => bool, message => string, balance => mixed]
     */
    public function getBalance(): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'SMS Gateway API key is not configured.',
                'balance' => null,
            ];
        }

        // Try primary URL first (.net), fallback to (.com) if needed
        foreach ([$this->primaryBaseUrl, $this->fallbackBaseUrl] as $baseUrl) {
            try {
                $response = Http::timeout(10)->get("{$baseUrl}/getBalance", [
                    'apikey' => $this->apiKey,
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $balance = $json['Balance'] ?? $json['balance'] ?? null;
                    $msg = $json['Message'] ?? $json['message'] ?? 'Successfully fetched balance';

                    return [
                        'success' => true,
                        'message' => $balance !== null ? "Current Balance: {$balance} BDT" : $msg,
                        'balance' => $balance ?? $json,
                        'raw' => $json,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning("BulkSmsDhaka getBalance error on {$baseUrl}: " . $e->getMessage());
            }
        }

        return [
            'success' => false,
            'message' => 'Could not connect to BulkSMS Dhaka gateway. Please check your network and API key.',
            'balance' => null,
        ];
    }

    /**
     * Send an SMS message using the BulkSMS Dhaka gateway.
     *
     * @param string $number Mobile number (e.g., 017XXXXXXXX)
     * @param string $message Text message content
     * @param string|null $senderId Optional custom sender ID / caller ID
     * @return array [success => bool, message => string, data => mixed]
     */
    public function sendSms(string $number, string $message, ?string $senderId = null): array
    {
        $formattedNumber = self::formatNumber($number);
        $callerId = $senderId ?: $this->getSenderId();

        if (empty($this->apiKey)) {
            Log::warning('BulkSmsDhaka: Cannot send SMS, BULKSMSDHAKA_API_KEY is not configured.', [
                'number' => $formattedNumber,
            ]);

            return [
                'success' => false,
                'message' => 'SMS Gateway API key is not configured.',
                'data' => null,
            ];
        }

        if (strlen($formattedNumber) !== 11 || !str_starts_with($formattedNumber, '01')) {
            return [
                'success' => false,
                'message' => 'Invalid Bangladeshi phone number format. Must be 11 digits starting with 01.',
                'data' => null,
            ];
        }

        $params = [
            'apikey' => $this->apiKey,
            'callerID' => $callerId,
            'number' => $formattedNumber,
            'message' => $message,
        ];

        // Try primary (.net) then fallback (.com)
        foreach ([$this->primaryBaseUrl, $this->fallbackBaseUrl] as $baseUrl) {
            try {
                // BulkSMS Dhaka accepts query/form parameters
                $response = Http::timeout(15)->get("{$baseUrl}/sendtext", $params);

                if ($response->successful()) {
                    $json = $response->json();

                    $rawSuccess = $json['Success'] ?? $json['success'] ?? null;
                    $status = (string) ($json['Status'] ?? $json['status'] ?? '');
                    $msg = $json['Message'] ?? $json['message'] ?? 'SMS sent successfully.';

                    // Check for spam word or failure response
                    $isExplicitFailure = $rawSuccess === false 
                        || $rawSuccess === 'false' 
                        || $status === '404' 
                        || str_contains(strtolower($msg), 'spam word')
                        || str_contains(strtolower($msg), 'failed')
                        || str_contains(strtolower($msg), 'invalid');

                    if ($isExplicitFailure) {
                        Log::warning('BulkSmsDhaka: SMS rejected by gateway', [
                            'number' => $formattedNumber,
                            'response' => $json,
                        ]);

                        return [
                            'success' => false,
                            'message' => $msg,
                            'data' => $json,
                        ];
                    }

                    Log::info('BulkSmsDhaka: SMS dispatched successfully', [
                        'number' => $formattedNumber,
                        'response' => $json,
                    ]);

                    return [
                        'success' => true,
                        'message' => $msg,
                        'data' => $json,
                    ];
                }
            } catch (\Throwable $e) {
                Log::error("BulkSmsDhaka sendSms error on {$baseUrl}: " . $e->getMessage());
            }
        }

        return [
            'success' => false,
            'message' => 'Failed to connect to SMS gateway.',
            'data' => null,
        ];
    }

    /**
     * Send bulk SMS messages to a list of numbers.
     *
     * @param array $numbers List of phone numbers
     * @param string $message Text message
     * @param string|null $senderId Optional custom sender ID
     * @return array [total => int, sent => int, failed => int, details => array]
     */
    public function sendBulkSms(array $numbers, string $message, ?string $senderId = null): array
    {
        $uniqueNumbers = array_unique(array_filter(array_map([self::class, 'formatNumber'], $numbers)));
        
        $total = count($uniqueNumbers);
        $sent = 0;
        $failed = 0;
        $details = [];

        foreach ($uniqueNumbers as $number) {
            $result = $this->sendSms($number, $message, $senderId);
            if ($result['success']) {
                $sent++;
            } else {
                $failed++;
            }
            $details[$number] = $result;

            // Micro-delay to avoid flooding gateway rate limits
            if ($total > 1) {
                usleep(50000); // 50ms
            }
        }

        return [
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'details' => $details,
        ];
    }
}
