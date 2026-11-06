<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\TelegramAccount;
use App\Models\TelegramMessage;
use App\Models\TelegramMonitoredChannel;
use App\Services\AuditService;
use App\Services\TelegramMtproto\TelethonClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramMtprotoController extends Controller
{
    protected TelethonClientService $telethonService;

    public function __construct(TelethonClientService $telethonService)
    {
        $this->telethonService = $telethonService;
    }

    /**
     * Main Telegram MTProto Dashboard.
     */
    public function index(Request $request)
    {
        $account = TelegramAccount::first();

        // Check health of Python Telethon daemon
        $serviceHealth = $this->telethonService->checkHealth();

        // If service is online and account has a saved session string, ensure session is restored
        if (($serviceHealth['online'] ?? false) && $account && $account->session_string && !($serviceHealth['connected'] ?? false)) {
            $restore = $this->telethonService->restoreSession($account->session_string, $account->api_id, $account->api_hash);
            if ($restore['connected'] ?? false) {
                $account->update([
                    'is_connected' => true,
                    'status' => 'connected',
                    'last_connected_at' => now(),
                ]);
                $serviceHealth = $this->telethonService->checkHealth();
            }
        }

        // Channels query with search & filter
        $channelsQuery = TelegramMonitoredChannel::query();

        if ($request->filled('search')) {
            $channelsQuery->search($request->search);
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $channelsQuery->where('type', $request->type);
        }

        if ($request->filled('monitored')) {
            if ($request->monitored === '1') {
                $channelsQuery->where('is_monitored', true);
            } elseif ($request->monitored === '0') {
                $channelsQuery->where('is_monitored', false);
            }
        }

        $channels = $channelsQuery->orderBy('is_monitored', 'desc')
            ->orderBy('title', 'asc')
            ->paginate(15)
            ->withQueryString();

        // Statistics
        $stats = [
            'total_channels' => TelegramMonitoredChannel::count(),
            'monitored_channels' => TelegramMonitoredChannel::where('is_monitored', true)->count(),
            'total_messages' => TelegramMessage::count(),
            'total_deals' => TelegramMessage::where('is_deal', true)->count(),
            'service_online' => $serviceHealth['online'] ?? false,
            'is_monitoring' => $serviceHealth['monitoring'] ?? false,
        ];

        // Recent messages preview
        $recentMessages = TelegramMessage::with(['channel', 'deal'])
            ->orderBy('posted_at', 'desc')
            ->take(8)
            ->get();

        return view('admin.telegram_mtproto.index', compact('account', 'serviceHealth', 'channels', 'stats', 'recentMessages'));
    }

    /**
     * Send login code to phone number via Telethon MTProto.
     */
    public function sendCode(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|min:8|max:20',
            'api_id' => 'nullable|numeric',
            'api_hash' => 'nullable|string|max:64',
        ]);

        $phone = preg_replace('/[^\+0-9]/', '', $validated['phone']);
        $apiId = ($validated['api_id'] ?? null) ?: config('services.telethon.api_id', env('TELEGRAM_API_ID', '2040'));
        $apiHash = ($validated['api_hash'] ?? null) ?: config('services.telethon.api_hash', env('TELEGRAM_API_HASH', 'b18441a1ff607e10a989891a5462e627'));

        // Save or update account draft
        $account = TelegramAccount::firstOrNew(['phone_number' => $phone]);
        $account->api_id = (string) $apiId;
        $account->api_hash = (string) $apiHash;
        $account->status = 'code_requested';
        $account->save();

        $result = $this->telethonService->sendCode($phone, (string) $apiId, (string) $apiHash);

        if ($result['success'] ?? false) {
            $account->update([
                'phone_code_hash' => $result['phone_code_hash'] ?? null,
                'status' => 'code_sent',
                'last_error' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Login code sent! Please check your Telegram app.',
                'phone' => $phone,
            ]);
        }

        $account->update([
            'status' => 'error',
            'last_error' => $result['error'] ?? 'Unknown error',
        ]);

        return response()->json([
            'success' => false,
            'message' => $result['error'] ?? 'Failed to send verification code.',
        ], 400);
    }

    /**
     * Verify the received OTP code.
     */
    public function verifyCode(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string|min:3|max:10',
        ]);

        $account = TelegramAccount::where('phone_number', $validated['phone'])->first();
        if (!$account) {
            return response()->json(['success' => false, 'message' => 'Account not found. Please send code first.'], 404);
        }

        $result = $this->telethonService->verifyCode(
            $validated['code'],
            $account->phone_number,
            $account->phone_code_hash
        );

        if ($result['success'] ?? false) {
            // Check if 2FA password is required
            if ($result['needs_2fa'] ?? false) {
                $account->update(['status' => '2fa_required']);
                return response()->json([
                    'success' => true,
                    'needs_2fa' => true,
                    'message' => 'Two-Step Verification password is required.',
                ]);
            }

            // Successfully authenticated
            $this->storeConnectedAccount($account, $result);

            // Automatically fetch available channels on login
            $this->syncChannelsInternal($account);

            return response()->json([
                'success' => true,
                'needs_2fa' => false,
                'message' => 'Telegram account connected successfully!',
                'user' => $result['user'] ?? null,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error'] ?? 'Invalid verification code.',
        ], 400);
    }

    /**
     * Verify 2FA password.
     */
    public function verify2FA(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        $account = TelegramAccount::where('status', '2fa_required')->first() ?: TelegramAccount::first();
        if (!$account) {
            return response()->json(['success' => false, 'message' => 'No active login session requiring 2FA.'], 404);
        }

        $result = $this->telethonService->verify2FA($validated['password']);

        if ($result['success'] ?? false) {
            $this->storeConnectedAccount($account, $result);

            // Automatically fetch available channels
            $this->syncChannelsInternal($account);

            return response()->json([
                'success' => true,
                'message' => 'Two-Step Verification successful! Connected.',
                'user' => $result['user'] ?? null,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error'] ?? 'Incorrect 2FA password.',
        ], 400);
    }

    /**
     * Disconnect Telegram account.
     */
    public function disconnect(Request $request)
    {
        $account = TelegramAccount::first();
        if ($account) {
            $this->telethonService->disconnect();
            $account->update([
                'is_connected' => false,
                'status' => 'disconnected',
                'session_string' => null,
            ]);
        }

        return back()->with('success', 'Telegram account disconnected successfully.');
    }

    /**
     * Refresh / fetch accessible channels from Telegram account.
     */
    public function fetchChannels(Request $request)
    {
        $account = TelegramAccount::where('is_connected', true)->first();
        if (!$account) {
            return back()->with('error', 'Please connect a Telegram account first.');
        }

        $count = $this->syncChannelsInternal($account);

        return back()->with('success', "Fetched and synchronized {$count} Telegram channels and groups.");
    }

    /**
     * Toggle individual channel monitoring.
     */
    public function toggleChannelMonitoring(Request $request, $id)
    {
        $channel = TelegramMonitoredChannel::findOrFail($id);
        $channel->is_monitored = !$channel->is_monitored;
        $channel->save();

        // Update live monitor with currently monitored IDs
        $this->updateLiveMonitor();

        return response()->json([
            'success' => true,
            'is_monitored' => $channel->is_monitored,
            'message' => $channel->is_monitored 
                ? "Channel '{$channel->title}' is now being monitored."
                : "Monitoring stopped for '{$channel->title}'.",
        ]);
    }

    /**
     * Bulk update channel monitoring.
     */
    public function bulkUpdateMonitoring(Request $request)
    {
        $action = $request->input('action'); // 'monitor' or 'stop'
        $channelIds = $request->input('channel_ids', []);

        if (empty($channelIds)) {
            return back()->with('error', 'No channels were selected.');
        }

        $isMonitored = ($action === 'monitor');
        TelegramMonitoredChannel::whereIn('id', $channelIds)->update(['is_monitored' => $isMonitored]);

        // Update live monitor
        $this->updateLiveMonitor();

        return back()->with('success', 'Selected channels updated successfully.');
    }

    /**
     * Trigger historical message sync for a channel.
     */
    public function syncChannelHistory(Request $request, $id)
    {
        $channel = TelegramMonitoredChannel::findOrFail($id);
        $limit = (int) $request->input('limit', 100);

        $channel->update([
            'sync_status' => 'syncing',
            'historical_limit' => $limit,
        ]);

        $res = $this->telethonService->syncChannelHistory($channel->channel_id, $limit);

        if ($res['success'] ?? false) {
            return response()->json([
                'success' => true,
                'message' => "Historical message sync started for '{$channel->title}' (limit: {$limit} messages).",
                'status' => 'syncing',
            ]);
        }

        $channel->update(['sync_status' => 'error']);

        return response()->json([
            'success' => false,
            'message' => $res['error'] ?? 'Failed to trigger historical sync.',
        ], 500);
    }

    /**
     * Start live monitoring across all selected channels.
     */
    public function startMonitoring(Request $request)
    {
        $monitoredIds = TelegramMonitoredChannel::where('is_monitored', true)->pluck('channel_id')->toArray();

        if (empty($monitoredIds)) {
            return back()->with('error', 'Please select at least one channel to monitor first.');
        }

        $res = $this->telethonService->startMonitoring($monitoredIds);

        if ($res['success'] ?? false) {
            return back()->with('success', $res['message'] ?? 'Live channel monitoring started successfully.');
        }

        return back()->with('error', $res['error'] ?? 'Failed to start live monitoring.');
    }

    /**
     * Stop live monitoring.
     */
    public function stopMonitoring(Request $request)
    {
        $res = $this->telethonService->stopMonitoring();

        return back()->with('success', 'Live channel monitoring stopped.');
    }

    /**
     * View all extracted Telegram messages.
     */
    public function messages(Request $request)
    {
        $query = TelegramMessage::with(['channel', 'deal'])->orderBy('posted_at', 'desc');

        if ($request->filled('channel_id')) {
            $query->where('channel_id', $request->channel_id);
        }

        if ($request->filled('deals_only') && $request->deals_only === '1') {
            $query->where('is_deal', true);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where('text', 'like', "%{$term}%");
        }

        $messages = $query->paginate(25)->withQueryString();
        $channels = TelegramMonitoredChannel::orderBy('title')->get();

        return view('admin.telegram_mtproto.messages', compact('messages', 'channels'));
    }

    /**
     * Polling endpoint for live progress and monitoring status.
     */
    public function liveStatus(Request $request)
    {
        $status = $this->telethonService->getMonitoringStatus();
        $health = $this->telethonService->checkHealth();

        return response()->json([
            'online' => $health['online'] ?? false,
            'monitoring' => $status['monitoring'] ?? ($health['monitoring'] ?? false),
            'monitored_count' => count($status['monitored_channels'] ?? []),
            'sync_progress' => $status['sync_progress'] ?? [],
            'total_messages' => TelegramMessage::count(),
            'total_deals' => TelegramMessage::where('is_deal', true)->count(),
        ]);
    }

    /**
     * Helper to store verified account.
     */
    protected function storeConnectedAccount(TelegramAccount $account, array $result): void
    {
        $userData = $result['user'] ?? [];
        $account->update([
            'session_string' => $result['session_string'] ?? $account->session_string,
            'telegram_user_id' => $userData['id'] ?? null,
            'first_name' => $userData['first_name'] ?? '',
            'last_name' => $userData['last_name'] ?? '',
            'username' => $userData['username'] ?? null,
            'is_connected' => true,
            'status' => 'connected',
            'last_error' => null,
            'last_connected_at' => now(),
        ]);

        AuditService::log(
            action: 'telegram_account_connected',
            entityType: TelegramAccount::class,
            entityId: $account->id,
            newValues: ['user' => $userData],
            actorType: 'admin',
            actorId: auth('admin')->id()
        );
    }

    /**
     * Internal helper to fetch dialogs from Telethon and sync into DB.
     */
    protected function syncChannelsInternal(TelegramAccount $account): int
    {
        $res = $this->telethonService->fetchChannels();
        $rawChannels = $res['channels'] ?? [];
        $count = 0;

        foreach ($rawChannels as $item) {
            $channelId = (int) ($item['id'] ?? 0);
            if (!$channelId) continue;

            TelegramMonitoredChannel::updateOrCreate(
                ['channel_id' => $channelId],
                [
                    'telegram_account_id' => $account->id,
                    'title' => $item['title'] ?? 'Untitled Channel',
                    'username' => !empty($item['username']) ? ltrim($item['username'], '@') : null,
                    'type' => $item['type'] ?? 'channel',
                    'participants_count' => $item['participants_count'] ?? null,
                    'access_hash' => $item['access_hash'] ?? null,
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Sync currently monitored channels with active Python listener.
     */
    protected function updateLiveMonitor(): void
    {
        $monitoredIds = TelegramMonitoredChannel::where('is_monitored', true)->pluck('channel_id')->toArray();
        $this->telethonService->startMonitoring($monitoredIds);
    }
}
