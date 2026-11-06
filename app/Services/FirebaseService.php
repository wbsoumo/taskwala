<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected ?string $projectId;
    protected ?string $credentialsFile;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id', 'taskwala-a9ab4');
        $this->credentialsFile = config('services.firebase.credentials_file');
    }

    /**
     * Obtain a valid Google OAuth2 Bearer Access Token for FCM v1.
     */
    public function getAccessToken(): ?string
    {
        return Cache::remember('firebase_fcm_oauth_token', 3000, function () {
            $credentials = null;
            if ($this->credentialsFile && file_exists($this->credentialsFile)) {
                $credentials = json_decode(file_get_contents($this->credentialsFile), true);
            }

            if (!$credentials && config('services.firebase.credentials_json')) {
                $jsonStr = config('services.firebase.credentials_json');
                $decoded = json_decode($jsonStr, true) ?: json_decode(base64_decode($jsonStr), true);
                if (is_array($decoded)) {
                    $credentials = $decoded;
                }
            }

            if (empty($credentials['client_email']) || empty($credentials['private_key'])) {
                Log::warning('Firebase credentials not configured or invalid.');
                return null;
            }

            $now = time();
            $header = ['alg' => 'RS256', 'typ' => 'JWT'];
            $payload = [
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ];

            $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
            $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));
            $dataToSign = $base64UrlHeader . '.' . $base64UrlPayload;

            $privateKey = $credentials['private_key'];
            $signature = '';
            $signed = openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);

            if (!$signed) {
                Log::error('Failed to sign Firebase OAuth2 JWT assertion.');
                return null;
            }

            $jwt = $dataToSign . '.' . $this->base64UrlEncode($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful() && !empty($response->json('access_token'))) {
                return $response->json('access_token');
            }

            Log::error('Firebase OAuth2 token request failed: ' . $response->body());
            return null;
        });
    }

    /**
     * Send Push Notification to a single device FCM token.
     */
    public function sendNotification(
        string $fcmToken,
        string $title,
        string $body,
        ?string $deeplink = null,
        ?string $imageUrl = null
    ): array {
        if (empty($fcmToken)) {
            return ['success' => false, 'error' => 'Empty FCM token'];
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Unable to generate Firebase access token'];
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $message = [
            'token' => $fcmToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => [
                'title' => (string) $title,
                'body' => (string) $body,
                'deeplink' => (string) ($deeplink ?? ''),
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'icon' => 'ic_notification',
                    'color' => '#2563EB',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'default_sound' => true,
                ],
            ],
        ];

        if (!empty($imageUrl)) {
            $message['notification']['image'] = $imageUrl;
            $message['data']['image'] = $imageUrl;
        }

        try {
            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post($url, ['message' => $message]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json('name'),
                ];
            }

            $errorMsg = $response->json('error.message') ?? $response->body();
            Log::warning("FCM send failed for token: {$errorMsg}");
            return [
                'success' => false,
                'error' => $errorMsg,
            ];
        } catch (\Throwable $e) {
            Log::error('Firebase notification exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
