<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Deal;
use App\Models\DealSyncLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\Deals\DealAggregatorService;
use App\Services\Deals\EarnKaroService;
use App\Services\Deals\TelegramDealService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DealsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Admin Controller',
            'email' => 'admin@taskwala.com',
            'password' => \Illuminate\Support\Facades\Hash::make('secret123'),
            'status' => 'active',
        ]);

        $this->user = User::create([
            'name' => 'Affiliate Hero',
            'email' => 'affiliate@taskwala.com',
            'mobile_number' => '9888877777',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    public function test_deal_model_generates_url_hash_and_detects_duplicates()
    {
        $url1 = 'https://www.amazon.in/dp/B08L5WHJ43?tag=affiliatetag-21&utm_source=telegram';
        $url2 = 'https://amazon.in/dp/B08L5WHJ43?utm_campaign=summer&ref=xyz';

        $hash1 = Deal::generateUrlHash($url1);
        $hash2 = Deal::generateUrlHash($url2);

        $this->assertEquals($hash1, $hash2, 'Normalized URL hashes should match ignoring tracking parameters');

        $deal = Deal::create([
            'title' => 'Sample Amazon Deal',
            'original_url' => $url1,
            'price' => 899.00,
            'original_price' => 2999.00,
            'category' => 'Electronics',
        ]);

        $this->assertNotEmpty($deal->public_id);
        $this->assertEquals($hash1, $deal->url_hash);
        $this->assertEquals(70, $deal->computed_discount);
    }

    public function test_earnkaro_service_converts_link_with_publisher_fallback()
    {
        Setting::set('deals_earnkaro_enabled', '1');
        Setting::set('deals_earnkaro_publisher_id', 'EK_TEST_PUBLISHER_99');

        $earnkaro = app(EarnKaroService::class);
        $res = $earnkaro->convertUrl('https://www.flipkart.com/sample-product/p/itm123');

        $this->assertTrue($res['success']);
        $this->assertStringContainsString('EK_TEST_PUBLISHER_99', $res['affiliate_url']);
        $this->assertStringContainsString('earnkaro.com', $res['affiliate_url']);
    }

    public function test_telegram_deal_service_parses_text_and_extracts_prices()
    {
        $telegramService = app(TelegramDealService::class);

        $msgText = "🔥 CRAZY LOOT! 🔥\nboAt Rockerz 255 Pro+ Neckband with 60H Playtime\nDeal Price: ₹899 (Was ₹3,990) - Flat 78% OFF!\nBuy Now: https://www.amazon.in/dp/B08L5WHJ43";

        $price = $telegramService->extractPrice($msgText);
        $origPrice = $telegramService->extractOriginalPrice($msgText);
        $discount = $telegramService->extractDiscount($msgText);
        $category = $telegramService->detectCategory($msgText);

        $this->assertEquals(899.00, $price);
        $this->assertEquals(3990.00, $origPrice);
        $this->assertEquals(78, $discount);
        $this->assertEquals('Electronics', $category);

        $payload = $telegramService->buildDealPayload(
            $msgText,
            'https://www.amazon.in/dp/B08L5WHJ43',
            '@lootdeals',
            'msg_101',
            'https://image.amazon.com/sample.jpg'
        );

        $this->assertStringContainsString('boAt Rockerz', $payload['title']);
        $this->assertEquals(899.00, $payload['price']);
        $this->assertEquals('Electronics', $payload['category']);
    }

    public function test_deal_aggregator_stores_deal_and_prevents_duplicate()
    {
        Setting::set('deals_earnkaro_enabled', '1');
        Setting::set('deals_earnkaro_publisher_id', 'PUB123');

        $aggregator = app(DealAggregatorService::class);

        $payload = [
            'title' => 'Redmi Note 13 5G',
            'description' => 'Great smartphone offer at 14999',
            'original_url' => 'https://www.amazon.in/dp/B09ABCDEF1',
            'source_channel' => '@dealschannel',
            'source_message_id' => '1001',
            'price' => 14999.00,
            'original_price' => 20999.00,
            'category' => 'Electronics',
        ];

        // 1. First insertion
        $res1 = $aggregator->processAndStoreDeal($payload);
        $this->assertEquals('added', $res1['status']);
        $this->assertDatabaseHas('deals', ['title' => 'Redmi Note 13 5G']);

        // 2. Duplicate insertion with extra tracking query param
        $payloadDuplicate = $payload;
        $payloadDuplicate['original_url'] = 'https://amazon.in/dp/B09ABCDEF1?tag=spam-21';

        $res2 = $aggregator->processAndStoreDeal($payloadDuplicate);
        $this->assertEquals('skipped', $res2['status']);
        $this->assertEquals(1, Deal::count(), 'Deal count should remain 1 because duplicate was skipped');
    }

    public function test_admin_can_view_deals_dashboard_and_create_deal()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.deals.index'))
            ->assertOk()
            ->assertSee('Deals Management');

        $createResponse = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.deals.store'), [
                'title' => 'Samsung Galaxy S24 Ultra',
                'original_url' => 'https://www.amazon.in/dp/B0CXYZ1234',
                'price' => 119999.00,
                'original_price' => 134999.00,
                'category' => 'Electronics',
                'commission_rate' => 'Up to 9% Profit',
                'status' => 'active',
            ]);

        $createResponse->assertRedirect(route('admin.deals.index'));
        $this->assertDatabaseHas('deals', ['title' => 'Samsung Galaxy S24 Ultra']);
    }

    public function test_admin_can_toggle_deal_status()
    {
        $deal = Deal::create([
            'title' => 'Test Slipper Offer',
            'original_url' => 'https://www.flipkart.com/slippers/p/123',
            'status' => 'active',
            'category' => 'Fashion',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.deals.toggle_status', $deal))
            ->assertRedirect();

        $this->assertEquals('expired', $deal->fresh()->status);
    }

    public function test_admin_can_update_deals_settings_and_test_connections()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.deals.settings'))
            ->assertOk()
            ->assertSee('Telegram Channels')
            ->assertSee('EarnKaro Affiliate API Integration');

        $updateResponse = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.deals.settings.update'), [
                'deals_telegram_bot_token' => '123456:FAKE_TOKEN_ABC',
                'deals_telegram_channels' => '@my_loot_channel, @bestdeals',
                'deals_telegram_enabled' => 1,
                'deals_earnkaro_api_key' => 'EK_KEY_999',
                'deals_earnkaro_publisher_id' => 'PUB_777',
                'deals_earnkaro_enabled' => 1,
                'deals_auto_fetch_interval' => '15',
                'deals_default_category' => 'General',
            ]);

        $updateResponse->assertRedirect(route('admin.deals.settings'));
        $this->assertEquals('PUB_777', Setting::get('deals_earnkaro_publisher_id'));

        Http::fake([
            'https://ekaro-api.affiliaters.in/*' => Http::response([
                'success' => 1,
                'data' => 'https://ekaro.in/sample_test',
                'randomPostID' => 'rhrh7ov',
            ], 200),
            'https://api.earnkaro.com/*' => Http::response([
                'status' => 'success',
                'converted_url' => 'https://ekaro.in/sample_test',
            ], 200),
            'https://api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => [
                    'id' => 1234567,
                    'first_name' => 'DealsBot',
                    'username' => 'taskwala_deals_bot',
                ],
            ], 200),
        ]);

        // Test EarnKaro Connection Endpoint
        $testEarnkaroRes = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.deals.settings.test_earnkaro'), [
                'api_key' => 'EK_KEY_999',
                'publisher_id' => 'PUB_777',
            ]);

        $testEarnkaroRes->assertOk()->assertJson(['success' => true]);

        // Test Telegram Connection Endpoint
        $testTelegramRes = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.deals.settings.test_telegram'), [
                'bot_token' => '123456:FAKE_TOKEN_ABC',
            ]);

        $testTelegramRes->assertOk()->assertJson(['success' => true]);

        // Test Telegram Channel Scraping Endpoint
        Http::fake([
            'https://t.me/s/my_loot_channel' => Http::response(
                '<div class="tgme_widget_message" data-post="my_loot_channel/100">' .
                '<div class="tgme_widget_message_text">Crazy Deal: Noise Smartwatch at Rs. 999 (MRP Rs. 3,999) https://www.amazon.in/dp/B00TEST</div>' .
                '<a href="https://www.amazon.in/dp/B00TEST">Buy</a>' .
                '</div></div></div>',
                200
            ),
        ]);

        $testChannelRes = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.deals.settings.test_channel'), [
                'channel' => '@my_loot_channel',
            ]);

        $testChannelRes->assertOk()->assertJson(['success' => true]);
    }

    public function test_user_can_browse_deals_and_redirect_increments_clicks()
    {
        $deal = Deal::create([
            'title' => 'Adidas Running Shoes Flat 60% Off',
            'original_url' => 'https://www.myntra.com/shoes/1234',
            'affiliate_url' => 'https://ekaro.in/enkr?id=123&url=https%3A%2F%2Fwww.myntra.com%2Fshoes%2F1234',
            'price' => 1999.00,
            'original_price' => 4999.00,
            'discount_percent' => 60,
            'status' => 'active',
            'category' => 'Fashion',
            'clicks_count' => 5,
        ]);

        // User views deals page
        $this->actingAs($this->user, 'web')
            ->get(route('user.deals.index'))
            ->assertOk()
            ->assertSee('Adidas Running Shoes Flat 60% Off')
            ->assertSee('60% OFF')
            ->assertSee('₹1,999');

        // User clicks grab deal redirect
        $redirectRes = $this->actingAs($this->user, 'web')
            ->get(route('user.deals.redirect', $deal->public_id));

        $redirectRes->assertRedirect($deal->affiliate_url);
        $this->assertEquals(6, $deal->fresh()->clicks_count);
    }

    public function test_api_v1_deals_endpoints()
    {
        Deal::create([
            'title' => 'OnePlus Nord CE 4',
            'original_url' => 'https://www.amazon.in/dp/B0CX12345',
            'affiliate_url' => 'https://ekaro.in/nord',
            'price' => 24999.00,
            'status' => 'active',
            'category' => 'Electronics',
        ]);

        $res = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/deals');

        $res->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data', 'categories', 'pagination']);

        $dealId = $res->json('data.0.id');

        // Click tracking endpoint
        $clickRes = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/deals/{$dealId}/click");

        $clickRes->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('redirect_url', 'https://ekaro.in/nord');
    }

    public function test_artisan_deals_sync_command()
    {
        $this->artisan('deals:sync', ['--limit' => 5])
            ->assertExitCode(0);

        $this->assertDatabaseHas('deal_sync_logs', [
            'channel' => 'All Channels',
        ]);
    }

    public function test_earnkaro_converter_api_handles_success_and_error_cases()
    {
        Setting::set('deals_earnkaro_enabled', '1');
        Setting::set('deals_earnkaro_api_key', 'TEST_TOKEN_123');
        Setting::set('deals_earnkaro_publisher_id', 'PUB_TEST');

        Http::fakeSequence()
            ->push(['success' => 1, 'data' => 'Check this out https://ekaro.in/converted123', 'randomPostID' => 'rhrh7ov'], 200)
            ->push(['error' => 1, 'message' => 'Please authenticate'], 401)
            ->push(['error' => 1, 'message' => 'Too many requests from this API key, please try again after a minute.'], 429);

        $earnkaro = app(EarnKaroService::class);

        // 1. Success test
        $res = $earnkaro->convertUrl('https://www.amazon.in/dp/B08L5WHJ43');
        $this->assertTrue($res['success']);
        $this->assertEquals('https://ekaro.in/converted123', $res['affiliate_url']);

        // 2. Auth error (401)
        $resAuth = $earnkaro->convertUrl('https://www.amazon.in/dp/B08L5WHJ43');
        $this->assertTrue($resAuth['success']);
        $this->assertStringContainsString('PUB_TEST', $resAuth['affiliate_url']);
        $this->assertStringContainsString('Authentication Error (401)', $resAuth['error']);

        // 3. Rate Limit test (429)
        $resRate = $earnkaro->testConnection('TEST_TOKEN_123');
        $this->assertFalse($resRate['success']);
        $this->assertStringContainsString('Rate Limit Exceeded', $resRate['message']);
    }
}
