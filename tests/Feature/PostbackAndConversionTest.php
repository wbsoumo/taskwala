<?php

namespace Tests\Feature;

use App\Models\AffiliateLink;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\PostbackProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PostbackAndConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_postback_test_unapproved_ip_rejected()
    {
        $provider = PostbackProvider::create([
            'name' => 'Network A',
            'slug' => 'network-a',
            'auth_method' => 'shared_secret',
            'secret_key' => 'secret_12345',
            'status' => 'active',
        ]);

        $provider->ipWhitelists()->create([
            'ip_address' => '1.2.3.4',
            'description' => 'Official Server IP',
        ]);

        // Request from unauthorized IP 5.6.7.8
        $response = $this->call(
            method: 'GET',
            uri: route('api.postback.handle', ['provider_slug' => 'network-a']),
            parameters: ['secret' => 'secret_12345', 'click_id' => 'CLK_123'],
            server: ['REMOTE_ADDR' => '5.6.7.8']
        );

        $response->assertStatus(403);
        $this->assertDatabaseMissing('conversions', ['click_id' => 'CLK_123']);
        $this->assertDatabaseHas('postback_logs', [
            'postback_provider_id' => $provider->id,
            'source_ip' => '5.6.7.8',
            'ip_whitelist_result' => false,
            'response_code' => 403,
        ]);
    }

    public function test_required_duplicate_test_same_postback_received_twice_is_idempotent()
    {
        $user = User::create([
            'name' => 'Affiliate A',
            'email' => 'affiliate@test.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $campaign = Campaign::create([
            'name' => 'Test Campaign',
            'slug' => 'test-campaign',
            'advertiser_name' => 'Advertiser A',
            'category' => 'Banking',
            'landing_url' => 'https://example.com?click_id={click_id}',
            'conversion_event' => 'account_opening',
            'advertiser_payout' => 100.00,
            'default_affiliate_payout' => 100.00,
            'currency' => 'INR',
            'status' => 'active',
            'postback_secret_key' => 'secret_999',
        ]);

        $link = AffiliateLink::create([
            'secure_token' => 'token_123',
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'allocated_affiliate_payout' => 100.00,
            'customer_payout' => 60.00,
            'affiliate_commission' => 40.00,
            'status' => 'active',
        ]);

        $click = Click::create([
            'click_id' => 'CLK_TEST_DUPLICATE',
            'link_id' => $link->id,
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'allocated_affiliate_payout' => 100.00,
            'customer_payout' => 60.00,
            'affiliate_commission' => 40.00,
            'ip_address' => '127.0.0.1',
            'status' => 'tracked',
            'created_at' => now(),
        ]);

        $provider = PostbackProvider::create([
            'name' => 'Network B',
            'slug' => 'network-b',
            'auth_method' => 'shared_secret',
            'secret_key' => 'secret_999',
            'status' => 'active',
        ]);

        $provider->ipWhitelists()->create(['ip_address' => '127.0.0.1']);

        // First Postback Request via GET
        $response1 = $this->call(
            method: 'GET',
            uri: route('api.postback.handle', ['provider_slug' => 'network-b']),
            parameters: ['secret' => 'secret_999', 'click_id' => 'CLK_TEST_DUPLICATE', 'conversion_id' => 'TX_1001', 'status' => 'approved'],
            server: ['REMOTE_ADDR' => '127.0.0.1']
        );

        $response1->assertStatus(200);

        // Second duplicate Postback Request with same click_id and TX ID
        $response2 = $this->call(
            method: 'GET',
            uri: route('api.postback.handle', ['provider_slug' => 'network-b']),
            parameters: ['secret' => 'secret_999', 'click_id' => 'CLK_TEST_DUPLICATE', 'conversion_id' => 'TX_1001', 'status' => 'approved'],
            server: ['REMOTE_ADDR' => '127.0.0.1']
        );

        $response2->assertStatus(200);

        // Assert exactly ONE conversion and ONE wallet transaction created!
        $this->assertEquals(1, \App\Models\Conversion::where('click_id', 'CLK_TEST_DUPLICATE')->count());
        $this->assertEquals(1, \App\Models\WalletTransaction::where('user_id', $user->id)->count());
        $this->assertEquals(40.00, (float) $user->wallet->fresh()->balance);
    }

    public function test_global_postback_endpoint_and_response_logging()
    {
        $user = User::create([
            'name' => 'Affiliate Global',
            'email' => 'global@test.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $campaign = Campaign::create([
            'name' => 'Global Offer Campaign',
            'slug' => 'global-offer-campaign',
            'advertiser_name' => 'Global Advertiser',
            'category' => 'Fintech',
            'landing_url' => 'https://example.com?click_id={click_id}',
            'conversion_event' => 'lead',
            'advertiser_payout' => 200.00,
            'default_affiliate_payout' => 150.00,
            'currency' => 'INR',
            'status' => 'active',
            'postback_secret_key' => 'offer_secret_abc',
        ]);

        $link = AffiliateLink::create([
            'secure_token' => 'global_token_123',
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'allocated_affiliate_payout' => 150.00,
            'customer_payout' => 100.00,
            'affiliate_commission' => 50.00,
            'status' => 'active',
        ]);

        $click = Click::create([
            'click_id' => 'CLK_GLOBAL_TEST',
            'link_id' => $link->id,
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'allocated_affiliate_payout' => 150.00,
            'customer_payout' => 100.00,
            'affiliate_commission' => 50.00,
            'ip_address' => '127.0.0.1',
            'status' => 'tracked',
            'created_at' => now(),
        ]);

        // Global postback hit via GET with correct offer secret key
        $response = $this->call(
            method: 'GET',
            uri: route('api.postback.handle', ['provider_slug' => 'global']),
            parameters: ['secret' => 'offer_secret_abc', 'click_id' => 'CLK_GLOBAL_TEST', 'conversion_id' => 'TX_GLOB_99', 'status' => 'approved'],
            server: ['REMOTE_ADDR' => '127.0.0.1']
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('postback_logs', [
            'source_ip' => '127.0.0.1',
            'response_code' => 200,
            'conversion_result' => true,
        ]);

        $log = \App\Models\PostbackLog::where('source_ip', '127.0.0.1')->latest('id')->first();
        $this->assertNotNull($log->response_payload);
        $this->assertEquals('success', $log->response_payload['status']);
        $this->assertEquals('CLK_GLOBAL_TEST', $log->response_payload['click_id']);
    }
}
