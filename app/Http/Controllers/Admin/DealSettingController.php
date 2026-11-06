<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DealSyncLog;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\Deals\DealAggregatorService;
use App\Services\Deals\EarnKaroService;
use App\Services\Deals\TelegramDealService;
use Illuminate\Http\Request;

class DealSettingController extends Controller
{
    /**
     * Show the Deals Settings page.
     */
    public function settings()
    {
        $settings = [
            'deals_telegram_bot_token' => Setting::get('deals_telegram_bot_token', ''),
            'deals_telegram_channels' => Setting::get('deals_telegram_channels', '@dealsmagnet, @lootdeals'),
            'deals_telegram_enabled' => Setting::get('deals_telegram_enabled', '1'),
            'deals_telegram_api_base' => Setting::get('deals_telegram_api_base', 'https://api.telegram.org'),
            'deals_earnkaro_api_key' => Setting::get('deals_earnkaro_api_key', ''),
            'deals_earnkaro_publisher_id' => Setting::get('deals_earnkaro_publisher_id', ''),
            'deals_earnkaro_api_endpoint' => Setting::get('deals_earnkaro_api_endpoint', 'https://api.earnkaro.com/api/v1/deals/generate'),
            'deals_earnkaro_enabled' => Setting::get('deals_earnkaro_enabled', '1'),
            'deals_auto_fetch_interval' => Setting::get('deals_auto_fetch_interval', '15'),
            'deals_default_category' => Setting::get('deals_default_category', 'General'),
        ];

        $recentLogs = DealSyncLog::orderBy('created_at', 'desc')->take(5)->get();

        return view('admin.deals.settings', compact('settings', 'recentLogs'));
    }

    /**
     * Update Deals Settings.
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'deals_telegram_bot_token' => 'nullable|string|max:255',
            'deals_telegram_channels' => 'nullable|string',
            'deals_telegram_enabled' => 'nullable|boolean',
            'deals_telegram_api_base' => 'nullable|url',
            'deals_earnkaro_api_key' => 'nullable|string|max:255',
            'deals_earnkaro_publisher_id' => 'nullable|string|max:255',
            'deals_earnkaro_api_endpoint' => 'nullable|url',
            'deals_earnkaro_enabled' => 'nullable|boolean',
            'deals_auto_fetch_interval' => 'required|in:5,15,30,60,120,360',
            'deals_default_category' => 'nullable|string|max:100',
        ]);

        $settingsToSave = [
            'deals_telegram_bot_token' => $validated['deals_telegram_bot_token'] ?? '',
            'deals_telegram_channels' => $validated['deals_telegram_channels'] ?? '',
            'deals_telegram_enabled' => $request->has('deals_telegram_enabled') ? '1' : '0',
            'deals_telegram_api_base' => !empty($validated['deals_telegram_api_base']) ? rtrim($validated['deals_telegram_api_base'], '/') : 'https://api.telegram.org',
            'deals_earnkaro_api_key' => $validated['deals_earnkaro_api_key'] ?? '',
            'deals_earnkaro_publisher_id' => $validated['deals_earnkaro_publisher_id'] ?? '',
            'deals_earnkaro_api_endpoint' => $validated['deals_earnkaro_api_endpoint'] ?? 'https://api.earnkaro.com/api/v1/deals/generate',
            'deals_earnkaro_enabled' => $request->has('deals_earnkaro_enabled') ? '1' : '0',
            'deals_auto_fetch_interval' => $validated['deals_auto_fetch_interval'],
            'deals_default_category' => $validated['deals_default_category'] ?? 'General',
        ];

        foreach ($settingsToSave as $key => $val) {
            Setting::set($key, (string) $val, 'deals');
        }

        AuditService::log(
            action: 'deals_settings_updated',
            entityType: Setting::class,
            newValues: $settingsToSave,
            actorType: 'admin',
            actorId: auth('admin')->id()
        );

        return redirect()->route('admin.deals.settings')->with('success', 'Deals settings saved successfully.');
    }

    /**
     * Test Telegram Bot Token connection.
     */
    public function testTelegram(Request $request, TelegramDealService $telegramService)
    {
        $token = $request->input('bot_token') ?: Setting::get('deals_telegram_bot_token');
        $apiBase = $request->input('api_base') ?: Setting::get('deals_telegram_api_base', 'https://api.telegram.org');
        $result = $telegramService->testBotToken($token, $apiBase);

        return response()->json($result);
    }

    /**
     * Test public Telegram channel scraping.
     */
    public function testChannel(Request $request, TelegramDealService $telegramService)
    {
        $channel = $request->input('channel');
        $botToken = $request->input('bot_token') ?: Setting::get('deals_telegram_bot_token');
        $apiBase = $request->input('api_base') ?: Setting::get('deals_telegram_api_base', 'https://api.telegram.org');

        if (empty($channel)) {
            $configured = Setting::get('deals_telegram_channels', '@dealsmagnet');
            $parts = array_filter(array_map('trim', explode(',', $configured)));
            $channel = reset($parts) ?: '@dealsmagnet';
        }

        $result = $telegramService->testPublicChannel($channel, $botToken, $apiBase);

        return response()->json($result);
    }

    /**
     * Test EarnKaro API / Publisher ID connection.
     */
    public function testEarnkaro(Request $request, EarnKaroService $earnkaroService)
    {
        $apiKey = $request->input('api_key') ?: Setting::get('deals_earnkaro_api_key');
        $publisherId = $request->input('publisher_id') ?: Setting::get('deals_earnkaro_publisher_id');
        $endpoint = $request->input('endpoint') ?: Setting::get('deals_earnkaro_api_endpoint');

        $result = $earnkaroService->testConnection($apiKey, $publisherId, $endpoint);

        return response()->json($result);
    }

    /**
     * Trigger manual sync now from admin.
     */
    public function syncNow(Request $request, DealAggregatorService $aggregator)
    {
        $channel = $request->input('channel');
        $result = $aggregator->syncAll($channel);

        if (isset($result['error'])) {
            return back()->with('error', 'Sync encountered an error: ' . $result['error']);
        }

        $msg = sprintf(
            'Sync completed successfully! Found: %d, Added: %d new deals, Skipped duplicates: %d, Failed: %d.',
            $result['total_found'],
            $result['total_added'],
            $result['total_skipped'],
            $result['total_failed']
        );

        return back()->with('success', $msg);
    }

    /**
     * View Deal synchronization logs.
     */
    public function logs(Request $request)
    {
        $query = DealSyncLog::query()->orderBy('created_at', 'desc');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $logs = $query->paginate(20)->withQueryString();

        $stats = [
            'total_runs' => DealSyncLog::count(),
            'successful_runs' => DealSyncLog::where('status', 'success')->count(),
            'partial_runs' => DealSyncLog::where('status', 'partial')->count(),
            'failed_runs' => DealSyncLog::where('status', 'failed')->count(),
        ];

        return view('admin.deals.logs', compact('logs', 'stats'));
    }

    /**
     * Clear synchronization logs.
     */
    public function clearLogs()
    {
        DealSyncLog::truncate();
        return back()->with('success', 'All synchronization logs cleared.');
    }
}
