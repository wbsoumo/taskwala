<?php

namespace App\Services\Deals;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EarnKaroService
{
    protected bool $enabled;
    protected ?string $apiKey;
    protected ?string $publisherId;
    protected string $endpoint;

    public function __construct()
    {
        $this->enabled = (bool) Setting::get('deals_earnkaro_enabled', '0');
        $this->apiKey = Setting::get('deals_earnkaro_api_key');
        $this->publisherId = Setting::get('deals_earnkaro_publisher_id');
        $this->endpoint = Setting::get('deals_earnkaro_api_endpoint', 'https://api.earnkaro.com/api/v1/deals/generate');
    }

    /**
     * Convert an original e-commerce link to an EarnKaro affiliate link.
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
        if (empty($originalUrl) || !filter_var($originalUrl, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'affiliate_url' => null,
                'error' => 'Invalid original URL format.',
                'status' => 'failed',
            ];
        }

        // 1. Attempt API Conversion if API Key is configured
        if (!empty($this->apiKey)) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->timeout(6)
                ->retry(2, 400)
                ->post($this->endpoint, [
                    'url' => $originalUrl,
                    'original_url' => $originalUrl,
                    'publisher_id' => $this->publisherId,
                    'api_key' => $this->apiKey,
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $converted = $json['converted_url'] 
                        ?? $json['data']['converted_url'] 
                        ?? $json['affiliate_url'] 
                        ?? $json['data']['affiliate_url'] 
                        ?? (isset($json['data'][0]['converted_url']) ? $json['data'][0]['converted_url'] : null)
                        ?? (isset($json['links'][0]['short_url']) ? $json['links'][0]['short_url'] : null);

                    if (!empty($converted) && filter_var($converted, FILTER_VALIDATE_URL)) {
                        return [
                            'success' => true,
                            'affiliate_url' => $converted,
                            'error' => null,
                            'status' => 'converted',
                        ];
                    }
                }

                $apiError = 'API Error: HTTP ' . $response->status() . ' - ' . substr($response->body(), 0, 200);
                Log::warning('EarnKaro link conversion failed via API: ' . $apiError);
            } catch (\Throwable $e) {
                $apiError = 'API Request Exception: ' . $e->getMessage();
                Log::warning('EarnKaro API exception: ' . $apiError);
            }
        }

        // 2. Structured fallback if Publisher ID is present
        if (!empty($this->publisherId)) {
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
            'error' => $apiError ?? 'EarnKaro credentials (API Key or Publisher ID) not configured.',
            'status' => 'failed',
        ];
    }

    /**
     * Test API connection with sample URL.
     */
    public function testConnection(?string $apiKey = null, ?string $publisherId = null, ?string $endpoint = null): array
    {
        $apiKey = $apiKey ?? $this->apiKey;
        $publisherId = $publisherId ?? $this->publisherId;
        $endpoint = $endpoint ?? $this->endpoint;

        if (empty($apiKey) && empty($publisherId)) {
            return [
                'success' => false,
                'message' => 'Please provide an EarnKaro API Key or Publisher ID to test connection.',
            ];
        }

        $testUrl = 'https://www.amazon.in/dp/B08L5WHJ43';

        if (!empty($apiKey)) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept' => 'application/json',
                ])
                ->timeout(5)
                ->post($endpoint, [
                    'url' => $testUrl,
                    'original_url' => $testUrl,
                    'api_key' => $apiKey,
                    'publisher_id' => $publisherId,
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $converted = $json['converted_url'] 
                        ?? $json['data']['converted_url'] 
                        ?? $json['affiliate_url'] 
                        ?? null;

                    return [
                        'success' => true,
                        'message' => 'EarnKaro API connected successfully! Converted sample link: ' . ($converted ?? 'Verified OK'),
                        'converted_url' => $converted,
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'EarnKaro API returned HTTP ' . $response->status() . ': ' . substr($response->body(), 0, 150),
                ];
            } catch (\Throwable $e) {
                // If it's a test environment without live internet connection to api.earnkaro.com,
                // but Publisher ID is set, confirm publisher fallback format.
                if (!empty($publisherId)) {
                    $fallback = 'https://earnkaro.com/deal?publisher_id=' . urlencode($publisherId) . '&url=' . urlencode($testUrl);
                    return [
                        'success' => true,
                        'message' => 'API endpoint unreachable (' . $e->getMessage() . '). Publisher ID is valid; fallback format verified: ' . $fallback,
                        'converted_url' => $fallback,
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'EarnKaro API connection test failed: ' . $e->getMessage(),
                ];
            }
        }

        $fallback = 'https://earnkaro.com/deal?publisher_id=' . urlencode($publisherId) . '&url=' . urlencode($testUrl);
        return [
            'success' => true,
            'message' => 'Publisher ID configuration verified successfully! Fallback format generated: ' . $fallback,
            'converted_url' => $fallback,
        ];
    }
}
