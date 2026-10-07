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

        $provider = PostbackProvider::with('ipWhitelists')
            ->where('slug', $providerSlug)
            ->first();

        if (!$provider || $provider->status !== 'active') {
            $this->logAttempt(
                requestId: $requestId,
                provider: $provider,
                request: $request,
                authResult: false,
                ipResult: false,
                clickResult: false,
                conversionResult: false,
                reason: 'Invalid or inactive postback provider.',
                responseCode: 404,
                startTime: $startTime
            );

            return [
                'status' => 'error',
                'message' => 'Postback provider not found or inactive.',
                'code' => 404,
            ];
        }

        // 1. IP Whitelist Check
        $ipAllowed = $this->verifyIpWhitelist($provider, $sourceIp);
        if (!$ipAllowed) {
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
                startTime: $startTime
            );

            return [
                'status' => 'error',
                'message' => 'IP address not whitelisted.',
                'code' => 403,
            ];
        }

        // 2. Provider Authentication Check
        $authPassed = $this->authenticateRequest($provider, $request);
        if (!$authPassed) {
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
                startTime: $startTime
            );

            return [
                'status' => 'error',
                'message' => 'Postback authentication failed.',
                'code' => 401,
            ];
        }

        // 3. Extract Parameters
        $clickId = $request->input('click_id') ?? $request->input('clickid') ?? $request->input('sub_id');
        $providerConversionId = $request->input('conversion_id') ?? $request->input('txid') ?? $request->input('transaction_id');
        $statusInput = strtolower($request->input('status', 'approved'));

        if (!$clickId) {
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
                startTime: $startTime
            );

            return [
                'status' => 'error',
                'message' => 'Missing click_id parameter.',
                'code' => 400,
            ];
        }

        // 4. Ingest Conversion via ConversionService
        try {
            $result = $this->conversionService->processConversion(
                clickId: $clickId,
                provider: $provider,
                providerConversionId: $providerConversionId,
                status: $statusInput,
                requestIp: $sourceIp,
                userAgent: $request->userAgent()
            );

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
                startTime: $startTime
            );

            return [
                'status' => 'success',
                'message' => $result['is_duplicate'] ? 'Duplicate conversion acknowledged (idempotent).' : 'Conversion recorded successfully.',
                'conversion_id' => $result['conversion']->public_id,
                'code' => 200,
            ];

        } catch (\Exception $e) {
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
                startTime: $startTime
            );

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 422,
            ];
        }
    }

    protected function verifyIpWhitelist(PostbackProvider $provider, string $ip): bool
    {
        if (!config('postback.ip_whitelist_enabled', true)) {
            return true;
        }

        $whitelists = $provider->ipWhitelists;
        if ($whitelists->isEmpty()) {
            // If no explicit whitelist configured for this provider, allow or check fallback
            return true;
        }

        foreach ($whitelists as $item) {
            if ($this->ipMatches($ip, $item->ip_address)) {
                return true;
            }
        }

        return false;
    }

    protected function ipMatches(string $ip, string $allowedIp): bool
    {
        if ($allowedIp === '*' || $allowedIp === $ip) {
            return true;
        }

        // CIDR notation matching
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

    protected function authenticateRequest(PostbackProvider $provider, Request $request): bool
    {
        switch ($provider->auth_method) {
            case 'shared_secret':
                $secret = $request->input('secret') ?? $request->header('X-Postback-Secret');
                return !empty($provider->secret_key) && hash_equals($provider->secret_key, (string)$secret);

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
