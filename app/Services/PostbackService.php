<?php

namespace App\Services;

use App\Models\PostbackLog;
use App\Models\PostbackProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostbackService
{
    protected ConversionService $conversionService;

    public function __construct(ConversionService $conversionService)
    {
        $this->conversionService = $conversionService;
    }

    /**
     * Handle incoming postback request.
     */
    public function handlePostback(string $providerSlug, Request $request): array
    {
        $startTime = microtime(true);
        $requestId = 'REQ_' . Str::ulid();
        $sourceIp = $request->ip() ?? '127.0.0.1';

        // 1. Resolve Postback Provider (Supports Global Provider or Network-Specific Provider)
        $provider = null;
        if ($providerSlug !== 'global') {
            $provider = PostbackProvider::with('ipWhitelists')
                ->where('slug', $providerSlug)
                ->first();

            if (!$provider || $provider->status !== 'active') {
                $response = [
                    'status' => 'error',
                    'message' => 'Postback provider not found or inactive.',
                    'code' => 404,
                ];

                $this->logAttempt(
                    requestId: $requestId,
                    provider: $provider,
                    request: $request,
                    authResult: false,
                    ipResult: false,
                    clickResult: false,
                    conversionResult: false,
                    reason: 'Invalid or inactive postback provider slug.',
                    responseCode: 404,
                    responsePayload: $response,
                    startTime: $startTime
                );

                return $response;
            }
        }

        // 2. IP Whitelist Check (Global + Provider-specific IP whitelists)
        $ipAllowed = $this->verifyIpWhitelist($provider, $sourceIp);
        if (!$ipAllowed) {
            $response = [
                'status' => 'error',
                'message' => "IP address {$sourceIp} is not authorized for postback.",
                'code' => 403,
            ];

            $this->logAttempt(
                requestId: $requestId,
                provider: $provider,
                request: $request,
                authResult: false,
                ipResult: false,
                clickResult: false,
                conversionResult: false,
                reason: "Unauthorized IP address: {$sourceIp}",
                responseCode: 403,
                responsePayload: $response,
                startTime: $startTime
            );

            return $response;
        }

        // 3. Authentication Check
        $authPassed = $this->authenticateRequest($provider, $request);
        if (!$authPassed) {
            $response = [
                'status' => 'error',
                'message' => 'Postback authentication verification failed.',
                'code' => 401,
            ];

            $this->logAttempt(
                requestId: $requestId,
                provider: $provider,
                request: $request,
                authResult: false,
                ipResult: true,
                clickResult: false,
                conversionResult: false,
                reason: 'Postback authentication verification failed.',
                responseCode: 401,
                responsePayload: $response,
                startTime: $startTime
            );

            return $response;
        }

        // 4. Extract Parameters
        $clickId = $request->input('click_id') ?? $request->input('clickid') ?? $request->input('sub_id');
        $providerConversionId = $request->input('conversion_id') ?? $request->input('txid') ?? $request->input('transaction_id');
        $statusInput = strtolower($request->input('status', 'approved'));

        if (!$clickId) {
            $response = [
                'status' => 'error',
                'message' => 'Missing click_id parameter.',
                'code' => 400,
            ];

            $this->logAttempt(
                requestId: $requestId,
                provider: $provider,
                request: $request,
                authResult: true,
                ipResult: true,
                clickResult: false,
                conversionResult: false,
                reason: 'Missing click_id parameter in postback payload.',
                responseCode: 400,
                responsePayload: $response,
                startTime: $startTime
            );

            return $response;
        }

        // 5. Ingest Conversion via ConversionService
        try {
            $result = $this->conversionService->processConversion(
                clickId: $clickId,
                provider: $provider,
                providerConversionId: $providerConversionId,
                status: $statusInput,
                requestIp: $sourceIp,
                userAgent: $request->userAgent()
            );

            $response = [
                'status' => 'success',
                'message' => $result['is_duplicate'] ? 'Duplicate conversion acknowledged (idempotent).' : 'Conversion recorded successfully.',
                'conversion_id' => $result['conversion']->public_id,
                'click_id' => $clickId,
                'code' => 200,
            ];

            $this->logAttempt(
                requestId: $requestId,
                provider: $provider,
                request: $request,
                authResult: true,
                ipResult: true,
                clickResult: true,
                conversionResult: true,
                reason: $result['is_duplicate'] ? 'Idempotent duplicate postback received.' : 'Conversion processed successfully.',
                responseCode: 200,
                responsePayload: $response,
                startTime: $startTime
            );

            return $response;

        } catch (\Exception $e) {
            $response = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 422,
            ];

            $this->logAttempt(
                requestId: $requestId,
                provider: $provider,
                request: $request,
                authResult: true,
                ipResult: true,
                clickResult: false,
                conversionResult: false,
                reason: $e->getMessage(),
                responseCode: 422,
                responsePayload: $response,
                startTime: $startTime
            );

            return $response;
        }
    }

    protected function verifyIpWhitelist(?PostbackProvider $provider, string $ip): bool
    {
        if (!config('postback.ip_whitelist_enabled', true)) {
            return true;
        }

        // If specific provider whitelist entries exist, check provider whitelist first
        if ($provider && $provider->ipWhitelists->isNotEmpty()) {
            foreach ($provider->ipWhitelists as $item) {
                if ($this->ipMatches($ip, $item->ip_address)) {
                    return true;
                }
            }
            return false;
        }

        // Check global provider whitelists or general whitelists (postback_provider_id = null or global provider)
        $globalWhitelists = \App\Models\PostbackIpWhitelist::whereNull('postback_provider_id')->get();
        if ($globalWhitelists->isNotEmpty()) {
            foreach ($globalWhitelists as $item) {
                if ($this->ipMatches($ip, $item->ip_address)) {
                    return true;
                }
            }
            return false;
        }

        // If no explicit whitelist entries configured anywhere, default to allow
        return true;
    }

    protected function ipMatches(string $ip, string $allowedIp): bool
    {
        if ($allowedIp === '*' || $allowedIp === $ip) {
            return true;
        }

        if (str_contains($allowedIp, '/')) {
            [$subnet, $mask] = explode('/', $allowedIp);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ipLong = ip2long($ip);
                $subnetLong = ip2long($subnet);
                $maskLong = -1 << (32 - (int)$mask);
                return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
            }
        }

        return false;
    }

    protected function authenticateRequest(?PostbackProvider $provider, Request $request): bool
    {
        $globalSecret = config('postback.global_secret');

        $incomingSecret = $request->input('secret') ?? $request->header('X-Postback-Secret');

        // Check global postback secret if configured
        if (!empty($globalSecret)) {
            if (hash_equals((string)$globalSecret, (string)$incomingSecret)) {
                return true;
            }
        }

        if (!$provider) {
            // Global endpoint without global secret configured or matched
            return empty($globalSecret);
        }

        switch ($provider->auth_method) {
            case 'shared_secret':
                return !empty($provider->secret_key) && hash_equals($provider->secret_key, (string)$incomingSecret);

            case 'api_key':
                $apiKey = $request->input('api_key') ?? $request->header('X-API-Key');
                return !empty($provider->secret_key) && hash_equals($provider->secret_key, (string)$apiKey);

            case 'hmac_signature':
                $headerSig = $request->header(config('postback.signature_header', 'X-Postback-Signature'));
                if (!$headerSig || empty($provider->secret_key)) return false;
                $computedSig = hash_hmac('sha256', $request->getContent(), $provider->secret_key);
                return hash_equals($computedSig, $headerSig);

            case 'ip_only':
            default:
                return true;
        }
    }

    protected function logAttempt(
        string $requestId,
        ?PostbackProvider $provider,
        Request $request,
        bool $authResult,
        bool $ipResult,
        bool $clickResult,
        bool $conversionResult,
        string $reason,
        int $responseCode,
        array $responsePayload,
        float $startTime
    ): PostbackLog {
        $processingTimeMs = (int) round((microtime(true) - $startTime) * 1000);

        return PostbackLog::create([
            'request_id' => $requestId,
            'postback_provider_id' => $provider?->id,
            'endpoint' => $request->fullUrl(),
            'source_ip' => $request->ip() ?? '127.0.0.1',
            'http_method' => $request->method(),
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
            'response_payload' => $responsePayload,
            'auth_result' => $authResult,
            'ip_whitelist_result' => $ipResult,
            'click_validation_result' => $clickResult,
            'conversion_result' => $conversionResult,
            'rejection_reason' => $reason,
            'response_code' => $responseCode,
            'processing_time_ms' => $processingTimeMs,
            'created_at' => now(),
        ]);
    }
}
