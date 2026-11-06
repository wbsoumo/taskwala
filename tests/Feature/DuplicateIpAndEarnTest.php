<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AffiliateLink;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DuplicateIpAndEarnTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_toggle_check_duplicate_ip_on_campaign()
    {
        $admin = Admin::create([
            'name' => 'Admin Controller',
            'email' => 'admin@taskwala.com',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.campaigns.store'), [
            'name' => 'Test Duplicate Check Campaign',
            'theme' => 'gradient_blue',
            'advertiser_name' => 'Fintech Corp',
            'category' => 'Finance',
            'landing_url' => 'https://example.com/offer?click_id={click_id}',
            'conversion_event' => 'registration',
            'advertiser_payout' => 200.00,
            'default_affiliate_payout' => 150.00,
            'currency' => 'INR',
            'status' => 'active',
            'check_duplicate_ip' => '1',
        ]);

        $response->assertRedirect();
        $campaign = Campaign::where('name', 'Test Duplicate Check Campaign')->first();
        $this->assertNotNull($campaign);
        $this->assertTrue((bool) $campaign->check_duplicate_ip);
    }

    public function test_duplicate_ip_warning_page_shown_when_enabled_and_ip_already_clicked()
    {
        $campaign = Campaign::create([
            'public_id' => 'CMP_' . Str::random(8),
            'name' => 'Kotak Savings Account',
            'advertiser_name' => 'Kotak Mahindra',
            'category' => 'Banking',
            'campaign_type' => 'cpa',
            'landing_url' => 'https://advertiser.example.com/landing?click_id={click_id}',
            'conversion_event' => 'account_open',
            'advertiser_payout' => 300.00,
            'default_affiliate_payout' => 200.00,
            'currency' => 'INR',
            'status' => 'active',
            'check_duplicate_ip' => true,
        ]);

        $user = User::create([
            'name' => 'Affiliate Hero',
            'email' => 'affiliate@taskwala.com',
            'mobile_number' => '9888877777',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $link = AffiliateLink::create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'secure_token' => 'TEST_TOKEN_' . Str::random(8),
            'allocated_affiliate_payout' => 200.00,
            'customer_payout' => 0.00,
            'affiliate_commission' => 200.00,
            'status' => 'active',
        ]);

        $testIp = '203.0.113.88';

        // Record a previous click with this IP
        Click::create([
            'click_id' => 'CLK_TEST12345',
            'link_id' => $link->id,
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'allocated_affiliate_payout' => 200.00,
            'customer_payout' => 0.00,
            'affiliate_commission' => 200.00,
            'ip_address' => $testIp,
            'user_agent' => 'Mozilla/5.0',
            'status' => 'tracked',
            'created_at' => now(),
        ]);

        // Second click from same IP should display the duplicate_ip warning view
        $response = $this->withServerVariables(['REMOTE_ADDR' => $testIp])
            ->get(route('tracking.redirect', ['token' => $link->secure_token]));

        $response->assertOk();
        $response->assertViewIs('public.duplicate_ip');
        $response->assertSee('Duplicate IP Detected');
        $response->assertSee($testIp);

        // However with force=1, it bypasses the warning and redirects
        $forceResponse = $this->withServerVariables(['REMOTE_ADDR' => $testIp])
            ->get(route('tracking.redirect', ['token' => $link->secure_token, 'force' => 1]));

        $forceResponse->assertRedirect();
    }

    public function test_duplicate_ip_check_skipped_when_disabled_on_campaign()
    {
        $campaign = Campaign::create([
            'public_id' => 'CMP_' . Str::random(8),
            'name' => 'Freecharge Pay Later',
            'advertiser_name' => 'Freecharge',
            'category' => 'Credit',
            'campaign_type' => 'cpa',
            'landing_url' => 'https://advertiser.example.com/landing?click_id={click_id}',
            'conversion_event' => 'kyc_done',
            'advertiser_payout' => 150.00,
            'default_affiliate_payout' => 100.00,
            'currency' => 'INR',
            'status' => 'active',
            'check_duplicate_ip' => false, // Disabled
        ]);

        $user = User::create([
            'name' => 'Affiliate Guy',
            'email' => 'guy@taskwala.com',
            'mobile_number' => '9777788888',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $link = AffiliateLink::create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'secure_token' => 'TEST_TOKEN_FREE_' . Str::random(8),
            'allocated_affiliate_payout' => 100.00,
            'customer_payout' => 0.00,
            'affiliate_commission' => 100.00,
            'status' => 'active',
        ]);

        $testIp = '203.0.113.99';

        // Previous click
        Click::create([
            'click_id' => 'CLK_TEST99999',
            'link_id' => $link->id,
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'allocated_affiliate_payout' => 100.00,
            'customer_payout' => 0.00,
            'affiliate_commission' => 100.00,
            'ip_address' => $testIp,
            'user_agent' => 'Mozilla/5.0',
            'status' => 'tracked',
            'created_at' => now(),
        ]);

        // Second click from same IP directly redirects because check_duplicate_ip is false
        $response = $this->withServerVariables(['REMOTE_ADDR' => $testIp])
            ->get(route('tracking.redirect', ['token' => $link->secure_token]));

        $response->assertRedirect();
    }

    public function test_api_check_ip_and_earn_endpoints()
    {
        $campaign = Campaign::create([
            'public_id' => 'CMP_' . Str::random(8),
            'name' => 'Axis Bank Savings',
            'advertiser_name' => 'Axis Bank',
            'category' => 'Banking',
            'campaign_type' => 'cpa',
            'landing_url' => 'https://advertiser.example.com/landing?click_id={click_id}',
            'conversion_event' => 'account_open',
            'advertiser_payout' => 500.00,
            'default_affiliate_payout' => 350.00,
            'currency' => 'INR',
            'status' => 'active',
            'check_duplicate_ip' => true,
        ]);

        $user = User::create([
            'name' => 'Affiliate Axis',
            'email' => 'axis@taskwala.com',
            'mobile_number' => '9666655555',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $testIp = '198.51.100.25';

        // Check fresh IP via API
        $checkResponse = $this->actingAs($user, 'sanctum')
            ->withServerVariables(['REMOTE_ADDR' => $testIp])
            ->getJson("/api/v1/campaigns/{$campaign->id}/check-ip");

        $checkResponse->assertOk();
        $checkResponse->assertJson([
            'check_enabled' => true,
            'is_duplicate' => false,
        ]);

        // Generate earn link via API
        $earnResponse = $this->actingAs($user, 'sanctum')
            ->withServerVariables(['REMOTE_ADDR' => $testIp])
            ->postJson("/api/v1/campaigns/{$campaign->id}/earn");

        $earnResponse->assertOk();
        $earnResponse->assertJsonStructure([
            'success',
            'link',
            'public_url',
            'payout_amount',
        ]);
        $this->assertEquals(350.00, $earnResponse->json('payout_amount'));
    }
}
