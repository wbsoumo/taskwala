<?php

namespace App\Services\TelegramMtproto;

use App\Models\Setting;
use App\Models\TelegramAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelethonClientService
{
    protected string $baseUrl;
    protected string $secret;
    protected int $timeout;

    public function __construct()
    {
        $host = config('services.telethon.host', env('TELETHON_SERVICE_HOST', '127.0.0.1'));
        $port = config('services.telethon.port', env('TELETHON_SERVICE_PORT', '5001'));
        $this->baseUrl = rtrim("http://{$host}:{$port}", '/');
        $this->secret = config('services.telethon.secret', env('TELEGRAM_MTPROTO_SECRET', 'taskwala_telethon_mtproto_sec_99182'));
        $this->timeout = 10;
    }

    /**
     * Check if Python Telethon service is running and responsive.
     */
    public function checkHealth(): array
    {
        try {
            $response = Http::timeout(3)->get("{$this->baseUrl}/health");
            if ($response->successful()) {
                return array_merge(['online' => true], $response->json());
            }
        } catch (\Throwable $e) {
            // Service not running or unreachable
        }

        return [
            'online' => false,
            'status' => 'offline',
            'message' => 'Python Telethon service is not running on ' . $this->baseUrl,
        ];
    }

    /**
     * Send login code to Telegram phone number.
     */
    public function sendCode(string $phone, ?string $apiId = null, ?string $apiHash = null): array
    {
        return $this->post('/auth/send-code', [
            'phone' => $phone,
            'api_id' => $apiId ? (int) $apiId : null,
            'api_hash' => $apiHash,
        ]);
    }

    /**
     * Verify OTP code.
     */
    public function verifyCode(string $code, ?string $phone = null, ?string $phoneCodeHash = null): array
    {
        return $this->post('/auth/verify-code', [
            'code' => $code,
            'phone' => $phone,
            'phone_code_hash' => $phoneCodeHash,
        ]);
    }

    /**
     * Verify 2FA password.
     */
    public function verify2FA(string $password): array
    {
        return $this->post('/auth/verify-2fa', [
            'password' => $password,
        ]);
    }

    /**
     * Restore string session from database.
     */
    public function restoreSession(string $sessionString, ?string $apiId = null, ?string $apiHash = null): array
    {
        return $this->post('/auth/restore-session', [
            'session_string' => $sessionString,
            'api_id' => $apiId ? (int) $apiId : null,
            'api_hash' => $apiHash,
        ]);
    }

    /**
     * Disconnect and terminate session.
     */
    public function disconnect(): array
    {
        return $this->post('/auth/disconnect', []);
    }

    /**
     * Fetch all Telegram channels and groups accessible to the account.
     */
    public function fetchChannels(): array
    {
        return $this->get('/channels');
    }

    /**
     * Start continuous monitoring for specified channel IDs.
     */
    public function startMonitoring(array $channelIds): array
    {
        $webhookUrl = url('/api/internal/telegram-webhook');

        return $this->post('/monitoring/start', [
            'channel_ids' => $channelIds,
            'webhook_url' => $webhookUrl,
            'secret' => $this->secret,
        ]);
    }

    /**
     * Stop continuous monitoring.
     */
    public function stopMonitoring(): array
    {
        return $this->post('/monitoring/stop', []);
    }

    /**
     * Get live monitoring and sync status.
     */
    public function getMonitoringStatus(): array
    {
        return $this->get('/monitoring/status');
    }

    /**
     * Trigger historical message sync for a channel.
     */
    public function syncChannelHistory(int|string $channelId, int $limit = 100): array
    {
        $webhookUrl = url('/api/internal/telegram-webhook');

        return $this->post('/channels/sync-history', [
            'channel_id' => (int) $channelId,
            'limit' => $limit,
            'webhook_url' => $webhookUrl,
            'secret' => $this->secret,
        ]);
    }

    /**
     * Attempt to launch python daemon in background if not running.
     */
    public function autoStartService(): bool
    {
        $health = $this->checkHealth();
        if ($health['online'] ?? false) {
            return true;
        }

        if (function_exists('proc_open') || function_exists('exec')) {
            $pythonPath = base_path('python_service/venv/bin/python3');
            $serverScript = base_path('python_service/server.py');

            if (file_exists($pythonPath) && file_exists($serverScript)) {
                $cmd = "{$pythonPath} {$serverScript} > /dev/null 2>&1 &";
                if (function_exists('exec')) {
                    @exec($cmd);
                }
                usleep(500000); // Wait 500ms
                $health = $this->checkHealth();
                return $health['online'] ?? false;
            }
        }

        return false;
    }

    /**
     * Internal GET request.
     */
    protected function get(string $endpoint): array
    {
        try {
            $res = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-Service-Secret' => $this->secret,
                    'Accept' => 'application/json',
                ])
                ->get("{$this->baseUrl}{$endpoint}");

            return $res->json() ?? ['success' => $res->successful()];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Could not connect to Telethon service: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Internal POST request.
     */
    protected function post(string $endpoint, array $data): array
    {
        try {
            $res = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-Service-Secret' => $this->secret,
                    'Accept' => 'application/json',
                ])
                ->post("{$this->baseUrl}{$endpoint}", $data);

            return $res->json() ?? ['success' => $res->successful()];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Telethon API request error: ' . $e->getMessage(),
            ];
        }
    }
}
