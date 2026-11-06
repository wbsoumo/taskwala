<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Deal;
use App\Models\TelegramAccount;
use App\Models\TelegramMessage;
use App\Models\TelegramMonitoredChannel;
use App\Services\TelegramMtproto\TelethonClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramMtprotoTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Admin Controller',
            'email' => 'admin@taskwala.com',
            'password' => \Illuminate\Support\Facades\Hash::make('secret123'),
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_mtproto_dashboard()
    {
        Http::fake([
            'http://127.0.0.1:5001/health' => Http::response([
                'status' => 'healthy',
                'connected' => false,
                'monitoring' => false,
                'monitored_channels' => [],
            ], 200),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.telegram_mtproto.index'));

        $response->assertOk()
            ->assertSee('Telegram Account')
            ->assertSee('Telethon Daemon')
            ->assertSee('Channel Monitoring');
    }

    public function test_admin_can_send_otp_code()
    {
        Http::fake([
            'http://127.0.0.1:5001/health' => Http::response([
                'status' => 'healthy',
                'connected' => false,
            ], 200),
            'http://127.0.0.1:5001/auth/send-code' => Http::response([
                'success' => true,
                'phone' => '+919876543210',
                'phone_code_hash' => 'fake_hash_123',
                'message' => 'Login code sent!',
            ], 200),
        ]);

        $res = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.telegram_mtproto.send_code'), [
                'phone' => '+919876543210',
            ]);

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'phone' => '+919876543210',
            ]);

        $this->assertDatabaseHas('telegram_accounts', [
            'phone_number' => '+919876543210',
            'phone_code_hash' => 'fake_hash_123',
            'status' => 'code_sent',
        ]);
    }

    public function test_admin_can_verify_code_and_connect_account()
    {
        TelegramAccount::create([
            'phone_number' => '+919876543210',
            'phone_code_hash' => 'hash_999',
            'status' => 'code_sent',
        ]);

        Http::fake([
            'http://127.0.0.1:5001/health' => Http::response([
                'status' => 'healthy',
                'connected' => false,
            ], 200),
            'http://127.0.0.1:5001/auth/verify-code' => Http::response([
                'success' => true,
                'needs_2fa' => false,
                'session_string' => '1BVtsOMGAAFakeSessionString999...',
                'user' => [
                    'id' => 12345678,
                    'first_name' => 'Soumojit',
                    'last_name' => 'Saha',
                    'username' => 'wbsoumo',
                    'phone' => '+919876543210',
                ],
            ], 200),
            'http://127.0.0.1:5001/channels' => Http::response([
                'success' => true,
                'channels' => [
                    [
                        'id' => -1001999888,
                        'title' => 'Taskbazi Loot',
                        'username' => 'taskbazi_loot',
                        'type' => 'channel',
                        'participants_count' => 1250,
                    ]
                ],
            ], 200),
        ]);

        $res = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.telegram_mtproto.verify_code'), [
                'phone' => '+919876543210',
                'code' => '54321',
            ]);

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'needs_2fa' => false,
            ]);

        $this->assertDatabaseHas('telegram_accounts', [
            'phone_number' => '+919876543210',
            'is_connected' => true,
            'username' => 'wbsoumo',
        ]);

        $this->assertDatabaseHas('telegram_monitored_channels', [
            'channel_id' => -1001999888,
            'title' => 'Taskbazi Loot',
            'username' => 'taskbazi_loot',
        ]);
    }

    public function test_admin_can_toggle_channel_monitoring()
    {
        $channel = TelegramMonitoredChannel::create([
            'channel_id' => -1001234567,
            'title' => 'Top Deals Channel',
            'username' => 'topdeals',
            'type' => 'channel',
            'is_monitored' => false,
        ]);

        Http::fake([
            'http://127.0.0.1:5001/monitoring/start' => Http::response([
                'success' => true,
                'monitoring' => true,
            ], 200),
        ]);

        $res = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.telegram_mtproto.channels.toggle', $channel->id));

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'is_monitored' => true,
            ]);

        $this->assertDatabaseHas('telegram_monitored_channels', [
            'id' => $channel->id,
            'is_monitored' => true,
        ]);
    }

    public function test_admin_can_trigger_historical_channel_sync()
    {
        $channel = TelegramMonitoredChannel::create([
            'channel_id' => -1001234567,
            'title' => 'Top Deals Channel',
            'is_monitored' => true,
        ]);

        Http::fake([
            'http://127.0.0.1:5001/health' => Http::response([
                'status' => 'healthy',
                'connected' => false,
            ], 200),
            'http://127.0.0.1:5001/channels/sync-history' => Http::response([
                'success' => true,
                'status' => 'syncing',
                'message' => 'Historical sync started',
            ], 200),
        ]);

        $res = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.telegram_mtproto.channels.sync_history', $channel->id), [
                'limit' => 50,
            ]);

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'syncing',
            ]);

        $this->assertEquals('syncing', $channel->fresh()->sync_status);
    }

    public function test_internal_webhook_processes_incoming_deals_and_prevents_duplicate()
    {
        $channel = TelegramMonitoredChannel::create([
            'channel_id' => -100555777,
            'title' => 'Loot Alerts',
            'is_monitored' => true,
        ]);

        $secret = config('services.telethon.secret', env('TELEGRAM_MTPROTO_SECRET', 'taskwala_telethon_mtproto_sec_99182'));

        $payload = [
            'channel_id' => -100555777,
            'message_id' => 4501,
            'text' => "🔥 MEGA LOOT! boAt Airdopes 141 Bluetooth Earbuds at ₹999 (Was ₹4,490) https://www.amazon.in/dp/B09N3ZNHTY",
            'media_type' => 'photo',
            'media_local_path' => 'telegram_media/sample.jpg',
            'extracted_urls' => ['https://www.amazon.in/dp/B09N3ZNHTY'],
            'posted_at' => now()->toIso8601String(),
            'is_edited' => false,
        ];

        // 1. Post to internal webhook
        $response = $this->withHeader('X-Telegram-Secret', $secret)
            ->postJson('/api/internal/telegram-webhook', $payload);

        $response->assertOk()
            ->assertJson([
                'processed' => true,
                'is_deal' => true,
            ]);

        // Verify TelegramMessage was saved
        $this->assertDatabaseHas('telegram_messages', [
            'channel_id' => -100555777,
            'message_id' => 4501,
            'is_deal' => true,
        ]);

        // Verify Deal was created with parsed pricing
        $this->assertDatabaseHas('deals', [
            'source_channel' => 'Loot Alerts',
            'price' => 999.00,
        ]);

        // 2. Post same message again (duplicate test) -> should be skipped
        $duplicateResponse = $this->withHeader('X-Telegram-Secret', $secret)
            ->postJson('/api/internal/telegram-webhook', $payload);

        $duplicateResponse->assertOk()
            ->assertJson([
                'processed' => false,
                'duplicate' => true,
            ]);
    }

    public function test_internal_webhook_rejects_unauthorized_requests()
    {
        $response = $this->withHeader('X-Telegram-Secret', 'wrong_token')
            ->postJson('/api/internal/telegram-webhook', [
                'channel_id' => -100555777,
                'message_id' => 1234,
            ]);

        $response->assertStatus(401);
    }
}
