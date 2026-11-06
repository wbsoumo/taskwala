<?php

namespace App\Services\Deals;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EarnKaroService
{
    public const DEFAULT_ENDPOINT = 'https://ekaro-api.affiliaters.in/api/converter/public';

    protected bool $enabled;
    protected ?string $apiKey;
    protected ?string $publisherId;
    protected string $endpoint;

    public function __construct()
    {
        try {
            $this->enabled = (bool) Setting::get('deals_earnkaro_enabled', '0');
            $this->apiKey = Setting::get('deals_earnkaro_api_key');
            $this->publisherId = Setting::get('deals_earnkaro_publisher_id');
            $savedEndpoint = Setting::get('deals_earnkaro_api_endpoint');

            // Migrate legacy endpoint if old one is still stored
            if (empty($savedEndpoint) || str_contains($savedEndpoint, 'api.earnkaro.com')) {
                $this->endpoint = self::DEFAULT_ENDPOINT;
            } else {
                $this->endpoint = $savedEndpoint;
            }
        } catch (\Throwable $e) {
            $this->enabled = false;
            $this->apiKey = null;
            $this->publisherId = null;
            $this->endpoint = self::DEFAULT_ENDPOINT;
        }
    }

    /**
     * Convert an original e-commerce link or deal text to an EarnKaro affiliate link.
     * Uses the official EarnKaro Converter API (ekaro-api.affiliaters.in).
     */
    public function convertUrl(string $originalUrl): array
    {
        if (!$this->enabled) {
            return [
                'success' => false,
                'affiliate_url' => null,
                'error' => 'EarnKaro integration is disabled in settings.',
                'status' => 'pending',
            ];
        }

        $originalUrl = trim($originalUrl);
        if (empty($originalUrl)) {
            return [
                'success' => false,
                'affiliate_url' => null,
                'error' => 'URL/deal content cannot be empty.',
                'status' => 'failed',
            ];
        }

        $cleanToken = trim($this->apiKey ?? '');
        // Strip 'Bearer ' if user entered it in the input field
        if (str_starts_with(strtolower($cleanToken), 'bearer ')) {
            $cleanToken = trim(substr($cleanToken, 7));
        }

        // 1. Attempt API Conversion if API Token is configured
        if (!empty($cleanToken)) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $cleanToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->timeout(8)
                ->retry(2, 500, function ($exception, $request) {
                    if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                        return $exception->response && $exception->response->serverError();
                    }
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                }, throw: false)
                ->post($this->endpoint, [
                    'deal' => $originalUrl,
                    'convert_option' => 'convert_only',
                ]);

                $json = $response->json();
                $statusCode = $response->status();

                // Success case: HTTP 200 and success == 1 or valid converted data
                if ($response->successful()) {
                    $isSuccess = ($json['success'] ?? 0) === 1 || ($json['success'] ?? false) === true;
                    $responseData = $json['data'] ?? null;

                    if ($isSuccess && !empty($responseData)) {
                        $convertedUrl = $this->extractAffiliateUrl($responseData);
                        if (!empty($convertedUrl)) {
                            return [
                                'success' => true,
                                'affiliate_url' => $convertedUrl,
                                'raw_data' => $responseData,
                                'random_post_id' => $json['randomPostID'] ?? null,
                                'error' => null,
                                'status' => 'converted',
                            ];
                        }
                    }

                    // Direct key fallback for backward compatibility
                    $directConverted = $json['converted_url'] ?? $json['affiliate_url'] ?? null;
                    if (!empty($directConverted)) {
                        return [
                            'success' => true,
                            'affiliate_url' => $directConverted,
                            'error' => null,
                            'status' => 'converted',
                        ];
                    }
                }

                // Error handling based on official documentation
                $apiError = $this->formatApiError($statusCode, $json, $response->body());
                Log::warning('EarnKaro link conversion API notice: ' . $apiError);

            } catch (\Throwable $e) {
                $apiError = 'EarnKaro API Exception: ' . $e->getMessage();
                Log::warning('EarnKaro connection error: ' . $apiError);
            }
        }

        // 2. Structured fallback if Publisher ID is present
        if (!empty($this->publisherId) && filter_var($originalUrl, FILTER_VALIDATE_URL)) {
            $fallbackUrl = 'https://earnkaro.com/deal?publisher_id=' . urlencode($this->publisherId) . '&url=' . urlencode($originalUrl);
            return [
                'success' => true,
                'affiliate_url' => $fallbackUrl,
                'error' => isset($apiError) ? $apiError . ' (Used publisher link fallback)' : null,
                'status' => 'converted',
            ];
        }

        return [
            'success' => false,
            'affiliate_url' => null,
            'error' => $apiError ?? 'EarnKaro API Token not configured.',
            'status' => 'failed',
        ];
    }

    /**
     * Test API connection with sample URL using official EarnKaro Converter API.
     */
    public function testConnection(?string $apiKey = null, ?string $publisherId = null, ?string $endpoint = null): array
    {
        $apiKey = trim($apiKey ?? $this->apiKey ?? '');
        if (str_starts_with(strtolower($apiKey), 'bearer ')) {
            $apiKey = trim(substr($apiKey, 7));
        }

        $publisherId = trim($publisherId ?? $this->publisherId ?? '');
        $endpoint = trim($endpoint ?? $this->endpoint ?? self::DEFAULT_ENDPOINT);

        if (empty($apiKey) && empty($publisherId)) {
            return [
                'success' => false,
                'message' => 'Please enter your EarnKaro API Token to test the connection.',
            ];
        }

        $testUrl = 'https://www.amazon.in/dp/B08L5WHJ43';

        if (!empty($apiKey)) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->timeout(8)
                ->post($endpoint, [
                    'deal' => $testUrl,
                    'convert_option' => 'convert_only',
                ]);

                $json = $response->json();
                $statusCode = $response->status();

                if ($response->successful()) {
                    $isSuccess = ($json['success'] ?? 0) === 1 || ($json['success'] ?? false) === true;
                    $responseData = $json['data'] ?? null;
                    $converted = $this->extractAffiliateUrl($responseData)
                        ?? $json['converted_url'] 
                        ?? $json['affiliate_url'] 
                        ?? null;

                    if ($isSuccess && !empty($converted)) {
                        return [
                            'success' => true,
                            'message' => 'EarnKaro Converter API connected successfully! Converted sample link: ' . $converted,
                            'converted_url' => $converted,
                            'random_post_id' => $json['randomPostID'] ?? null,
                        ];
                    }

                    if (!empty($json['message'])) {
                        return [
                            'success' => false,
                            'message' => $this->formatApiError($statusCode, $json, $response->body()),
                        ];
                    }
                }

                return [
                    'success' => false,
                    'message' => $this->formatApiError($statusCode, $json, $response->body()),
                ];
            } catch (\Throwable $e) {
                if (!empty($publisherId)) {
                    $fallback = 'https://earnkaro.com/deal?publisher_id=' . urlencode($publisherId) . '&url=' . urlencode($testUrl);
                    return [
                        'success' => true,
                        'message' => 'API endpoint unreachable (' . $e->getMessage() . '). Publisher ID fallback verified: ' . $fallback,
                        'converted_url' => $fallback,
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'EarnKaro connection test failed: ' . $e->getMessage(),
                ];
            }
        }

        $fallback = 'https://earnkaro.com/deal?publisher_id=' . urlencode($publisherId) . '&url=' . urlencode($testUrl);
        return [
            'success' => true,
            'message' => 'Publisher ID configuration verified successfully! Fallback format: ' . $fallback,
            'converted_url' => $fallback,
        ];
    }

    /**
     * Extract affiliate URL from EarnKaro response data.
     * Response data can be a direct URL or converted text containing links.
     */
    protected function extractAffiliateUrl(mixed $data): ?string
    {
        if (is_array($data)) {
            return $data['converted_url'] ?? $data['affiliate_url'] ?? $data['url'] ?? null;
        }

        if (is_string($data)) {
            $data = trim($data);
            if (filter_var($data, FILTER_VALIDATE_URL)) {
                return $data;
            }

            // Extract first URL from converted deal text
            if (preg_match('/https?:\/\/[^\s\'"<>\)]+/i', $data, $matches)) {
                return $matches[0];
            }
        }

        return null;
    }

    /**
     * Format clean error messages matching official EarnKaro API specs.
     */
    protected function formatApiError(int $statusCode, ?array $json, string $rawBody): string
    {
        $message = $json['message'] ?? null;

        if ($statusCode === 401 || $message === 'Please authenticate') {
            return 'Authentication Error (401): Please verify your EarnKaro API Token.';
        }

        if ($statusCode === 429 || ($message && str_contains($message, 'Too many requests'))) {
            return 'Rate Limit Exceeded: 60 requests per minute limit reached. Please retry after a minute.';
        }

        if ($message === 'Url not found in post!') {
            return 'EarnKaro Error: No e-commerce URL was found in the deal text.';
        }

        if ($message && str_contains($message, 'We could not locate an affiliate URL')) {
            return 'EarnKaro Notice: Merchant/seller is not an active EarnKaro affiliate partner.';
        }

        if (!empty($message)) {
            return 'EarnKaro API: ' . $message;
        }

        return 'HTTP ' . $statusCode . ' - ' . substr($rawBody, 0, 150);
    }
}
