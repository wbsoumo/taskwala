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
    protected ?string $cachedPythonBin = null;

    public function __construct()
    {
        $host = config('services.telethon.host', env('TELETHON_SERVICE_HOST', '127.0.0.1'));
        $port = config('services.telethon.port', env('TELETHON_SERVICE_PORT', '5001'));
        $this->baseUrl = rtrim("http://{$host}:{$port}", '/');
        $this->secret = config('services.telethon.secret', env('TELEGRAM_MTPROTO_SECRET', 'taskwala_telethon_mtproto_sec_99182'));
        $this->timeout = 5;
    }

    /**
     * Check if Python Telethon service is running (Daemon or cPanel CLI mode).
     */
    public function checkHealth(): array
    {
        // 1. Check HTTP daemon first
        try {
            $response = Http::timeout(2)->get("{$this->baseUrl}/health");
            if ($response->successful()) {
                $data = $response->json();
                return array_merge([
                    'online' => true,
                    'mode' => 'daemon',
                    'status' => 'daemon_active',
                ], $data);
            }
        } catch (\Throwable $e) {
            // HTTP daemon not reachable (expected on shared cPanel)
        }

        // 2. Check cPanel Direct CLI mode
        $cliStatus = $this->runCli('check-status');
        if ($cliStatus['success'] ?? false) {
            $account = TelegramAccount::first();
            $connected = (bool) ($account && $account->is_connected && $account->session_string);

            return [
                'online' => true,
                'mode' => 'cpanel_cli',
                'status' => 'cpanel_cli_active',
                'message' => 'Running in cPanel Direct Execution Mode (Fully Functional)',
                'telethon_installed' => true,
                'telethon_version' => $cliStatus['telethon_version'] ?? '1.45.0',
                'connected' => $cliStatus['connected'] ?? $connected,
                'user' => $cliStatus['user'] ?? ($account ? [
                    'id' => $account->telegram_user_id,
                    'first_name' => $account->first_name,
                    'username' => $account->username,
                    'phone' => $account->phone_number,
                ] : null),
                'monitoring' => false,
            ];
        }

        // 3. Telethon or Python missing
        $python = $this->resolvePythonBinary();
        return [
            'online' => false,
            'mode' => 'offline',
            'status' => 'offline',
            'python_found' => !empty($python),
            'python_path' => $python,
            'message' => $cliStatus['error'] ?? 'Python Telethon is not installed or configured.',
        ];
    }

    /**
     * Send login code to Telegram phone number.
     */
    public function sendCode(string $phone, ?string $apiId = null, ?string $apiHash = null): array
    {
        // 1. Try daemon if online
        if ($this->isDaemonOnline()) {
            $res = $this->post('/auth/send-code', [
                'phone' => $phone,
                'api_id' => $apiId ? (int) $apiId : null,
                'api_hash' => $apiHash,
            ]);
            if ($res['success'] ?? false) {
                return $res;
            }
        }

        // 2. Fall back to cPanel Direct CLI
        $args = ['--phone' => $phone];
        if ($apiId) $args['--api-id'] = (string) $apiId;
        if ($apiHash) $args['--api-hash'] = (string) $apiHash;

        return $this->runCli('send-code', $args);
    }

    /**
     * Verify OTP code.
     */
    public function verifyCode(
        string $code,
        ?string $phone = null,
        ?string $phoneCodeHash = null,
        ?string $sessionString = null,
        ?string $apiId = null,
        ?string $apiHash = null
    ): array {
        // 1. Try daemon if online
        if ($this->isDaemonOnline()) {
            $res = $this->post('/auth/verify-code', [
                'code' => $code,
                'phone' => $phone,
                'phone_code_hash' => $phoneCodeHash,
            ]);
            if ($res['success'] ?? false) {
                return $res;
            }
        }

        // 2. Fall back to cPanel Direct CLI
        $args = [
            '--phone' => $phone ?: '',
            '--code' => $code,
            '--hash' => $phoneCodeHash ?: '',
        ];
        if ($sessionString) $args['--session'] = $sessionString;
        if ($apiId) $args['--api-id'] = (string) $apiId;
        if ($apiHash) $args['--api-hash'] = (string) $apiHash;

        return $this->runCli('verify-code', $args);
    }

    /**
     * Verify 2FA password.
     */
    public function verify2FA(
        string $password,
        ?string $sessionString = null,
        ?string $apiId = null,
        ?string $apiHash = null
    ): array {
        // 1. Try daemon if online
        if ($this->isDaemonOnline()) {
            $res = $this->post('/auth/verify-2fa', [
                'password' => $password,
            ]);
            if ($res['success'] ?? false) {
                return $res;
            }
        }

        // 2. Fall back to cPanel Direct CLI
        $args = ['--password' => $password];
        if ($sessionString) $args['--session'] = $sessionString;
        if ($apiId) $args['--api-id'] = (string) $apiId;
        if ($apiHash) $args['--api-hash'] = (string) $apiHash;

        return $this->runCli('verify-2fa', $args);
    }

    /**
     * Restore string session from database.
     */
    public function restoreSession(string $sessionString, ?string $apiId = null, ?string $apiHash = null): array
    {
        if ($this->isDaemonOnline()) {
            return $this->post('/auth/restore-session', [
                'session_string' => $sessionString,
                'api_id' => $apiId ? (int) $apiId : null,
                'api_hash' => $apiHash,
            ]);
        }

        // For CLI, test session by running check-status with session
        $args = ['--session' => $sessionString];
        if ($apiId) $args['--api-id'] = (string) $apiId;
        if ($apiHash) $args['--api-hash'] = (string) $apiHash;

        return $this->runCli('check-status', $args);
    }

    /**
     * Disconnect and terminate session.
     */
    public function disconnect(): array
    {
        if ($this->isDaemonOnline()) {
            $this->post('/auth/disconnect', []);
        }

        return ['success' => true, 'message' => 'Disconnected successfully.'];
    }

    /**
     * Fetch all Telegram channels and groups accessible to the account.
     */
    public function fetchChannels(?string $sessionString = null, ?string $apiId = null, ?string $apiHash = null): array
    {
        if ($this->isDaemonOnline()) {
            $res = $this->get('/channels');
            if ($res['success'] ?? false) {
                return $res;
            }
        }

        if (!$sessionString) {
            $account = TelegramAccount::first();
            $sessionString = $account?->session_string;
            $apiId = $apiId ?: $account?->api_id;
            $apiHash = $apiHash ?: $account?->api_hash;
        }

        if (!$sessionString) {
            return ['success' => false, 'channels' => [], 'error' => 'No active Telegram session found.'];
        }

        $args = ['--session' => $sessionString];
        if ($apiId) $args['--api-id'] = (string) $apiId;
        if ($apiHash) $args['--api-hash'] = (string) $apiHash;

        return $this->runCli('fetch-channels', $args);
    }

    /**
     * Start continuous monitoring for specified channel IDs.
     */
    public function startMonitoring(array $channelIds): array
    {
        if ($this->isDaemonOnline()) {
            $webhookUrl = url('/api/internal/telegram-webhook');
            return $this->post('/monitoring/start', [
                'channel_ids' => $channelIds,
                'webhook_url' => $webhookUrl,
                'secret' => $this->secret,
            ]);
        }

        // On cPanel without daemon, monitoring is driven by scheduled cron job
        return [
            'success' => true,
            'monitoring' => true,
            'mode' => 'cron_scheduled',
            'channels_count' => count($channelIds),
            'message' => 'Channels marked for monitoring. Sync will run automatically via cPanel Cron.',
        ];
    }

    /**
     * Stop continuous monitoring.
     */
    public function stopMonitoring(): array
    {
        if ($this->isDaemonOnline()) {
            return $this->post('/monitoring/stop', []);
        }

        return [
            'success' => true,
            'monitoring' => false,
            'message' => 'Channel monitoring stopped.',
        ];
    }

    /**
     * Get live monitoring and sync status.
     */
    public function getMonitoringStatus(): array
    {
        if ($this->isDaemonOnline()) {
            return $this->get('/monitoring/status');
        }

        $monitoredCount = \App\Models\TelegramMonitoredChannel::where('is_monitored', true)->count();
        return [
            'success' => true,
            'monitoring' => $monitoredCount > 0,
            'mode' => 'cpanel_cli',
            'monitored_channels' => \App\Models\TelegramMonitoredChannel::where('is_monitored', true)->pluck('channel_id')->toArray(),
            'sync_progress' => [],
        ];
    }

    /**
     * Trigger historical message sync for a channel.
     */
    public function syncChannelHistory(
        int|string $channelId,
        int $limit = 50,
        ?string $sessionString = null,
        ?string $apiId = null,
        ?string $apiHash = null
    ): array {
        // 1. Try daemon if online
        if ($this->isDaemonOnline()) {
            $webhookUrl = url('/api/internal/telegram-webhook');
            $res = $this->post('/channels/sync-history', [
                'channel_id' => (int) $channelId,
                'limit' => $limit,
                'webhook_url' => $webhookUrl,
                'secret' => $this->secret,
            ]);
            if ($res['success'] ?? false) {
                return $res;
            }
        }

        // 2. Fall back to cPanel Direct CLI
        if (!$sessionString) {
            $account = TelegramAccount::first();
            $sessionString = $account?->session_string;
            $apiId = $apiId ?: $account?->api_id;
            $apiHash = $apiHash ?: $account?->api_hash;
        }

        if (!$sessionString) {
            return ['success' => false, 'error' => 'No active Telegram session found.'];
        }

        $webhookUrl = url('/api/internal/telegram-webhook');
        $args = [
            '--session' => $sessionString,
            '--channel-id' => (string) $channelId,
            '--limit' => (string) $limit,
            '--webhook-url' => $webhookUrl,
            '--secret' => $this->secret,
        ];
        if ($apiId) $args['--api-id'] = (string) $apiId;
        if ($apiHash) $args['--api-hash'] = (string) $apiHash;

        $res = $this->runCli('sync-history', $args);

        // Directly process messages into DB if returned, avoiding loopback HTTP dependency on cPanel
        if (($res['success'] ?? false) && !empty($res['messages']) && is_array($res['messages'])) {
            try {
                $processor = app(TelegramMessageProcessor::class);
                $processor->processPayload(['batch' => true, 'messages' => $res['messages']]);
            } catch (\Throwable $te) {
                Log::warning('Direct batch processing error: ' . $te->getMessage());
            }
        }

        return $res;
    }

    /**
     * Run action via standalone Python CLI tool.
     */
    public function runCli(string $action, array $args = []): array
    {
        $python = $this->resolvePythonBinary();
        if (!$python) {
            return [
                'success' => false,
                'error' => 'Python 3 executable not found on this server. Please install Python 3 or specify TELEGRAM_PYTHON_PATH in .env',
            ];
        }

        $script = base_path('python_service/telethon_cli.py');
        if (!file_exists($script)) {
            return [
                'success' => false,
                'error' => 'telethon_cli.py not found at ' . $script,
            ];
        }

        $cmd = [$python, $script, $action];
        foreach ($args as $key => $val) {
            if ($val === null || $val === '') continue;
            if (is_int($key)) {
                $cmd[] = (string) $val;
            } else {
                $cmd[] = $key;
                $cmd[] = (string) $val;
            }
        }

        $res = $this->execProc($cmd, 60);

        if (!empty($res['stdout'])) {
            $json = json_decode($res['stdout'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                return $json;
            }
        }

        $errMsg = !empty($res['stderr']) ? $res['stderr'] : ($res['stdout'] ?: 'CLI returned empty response');
        return [
            'success' => false,
            'error' => $errMsg,
            'exit_code' => $res['exit_code'],
        ];
    }

    /**
     * Resolve the best available Python 3 executable on cPanel or standard server.
     */
    public function resolvePythonBinary(): ?string
    {
        if ($this->cachedPythonBin !== null) {
            return $this->cachedPythonBin ?: null;
        }

        $candidates = array_filter([
            config('services.telegram.python_path'),
            env('TELEGRAM_PYTHON_PATH'),
            base_path('python_service/venv/bin/python3'),
            '/usr/bin/python3',
            '/usr/local/bin/python3',
            'python3',
            'python',
        ]);

        foreach ($candidates as $bin) {
            if (str_starts_with($bin, '/') && !file_exists($bin)) {
                continue;
            }
            $out = $this->execProc([$bin, '--version'], 5);
            if ($out['exit_code'] === 0) {
                $this->cachedPythonBin = $bin;
                return $bin;
            }
        }

        $this->cachedPythonBin = '';
        return null;
    }

    /**
     * Check if background daemon is actively responding.
     */
    protected function isDaemonOnline(): bool
    {
        try {
            $res = Http::timeout(1.5)->get("{$this->baseUrl}/health");
            return $res->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Execute process using proc_open (works on cPanel where exec/shell_exec are disabled).
     */
    protected function execProc(array $cmd, int $timeoutSeconds = 30): array
    {
        $commandStr = implode(' ', array_map('escapeshellarg', $cmd));
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($commandStr, $descriptors, $pipes, base_path());
        if (!is_resource($process)) {
            return [
                'exit_code' => -1,
                'stdout' => '',
                'stderr' => 'proc_open failed: function may be disabled or restricted.',
            ];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [
            'exit_code' => $exitCode,
            'stdout' => trim($stdout),
            'stderr' => trim($stderr),
        ];
    }

    /**
     * Internal GET request to daemon.
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
     * Internal POST request to daemon.
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
