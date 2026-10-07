<?php

namespace Tests\Feature;

use App\Models\AffiliateLink;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\Conversion;
use App\Models\PayoutSnapshot;
use App\Models\User;
use App\Services\ConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ImmutablePayoutSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_historical_data_test_old_conversion_payout_remains_immutable_when_link_changes()
    {
        $user = User::create([
            'name' => 'Affiliate User',
            'email' => 'user@test.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $campaign = Campaign::create([
            'name' => 'Kotak Campaign',
            'slug' => 'kotak-campaign',
            'advertiser_name' => 'Kotak Bank',
            'category' => 'Banking',
            'landing_url' => 'https://example.com',
            'conversion_event' => 'account_opening',
            'advertiser_payout' => 100.00,
            'default_affiliate_payout' => 100.00,
            'currency' => 'INR',
            'status' => 'active',
        ]);

        // Link 1: Customer Payout = ₹60, Affiliate Commission = ₹40
        $link1 = AffiliateLink::create([
            'secure_token' => 'token_v1',
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'allocated_affiliate_payout' => 100.00,
            'customer_payout' => 60.00,
            'affiliate_commission' => 40.00,
            'status' => 'active',
        ]);

        $click1 = Click::create([
            'click_id' => 'CLK_HISTORICAL_1001',
            'link_id' => $link1->id,
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'allocated_affiliate_payout' => 100.00,
            'customer_payout' => 60.00,
            'affiliate_commission' => 40.00,
            'ip_address' => '127.0.0.1',
            'status' => 'tracked',
            'created_at' => now(),
        ]);

        // Process Conversion #1001
        /** @var ConversionService $conversionService */
        $conversionService = app(ConversionService::class);
        $result = $conversionService->processConversion(
            clickId: 'CLK_HISTORICAL_1001',
            providerConversionId: 'HIST_TX_1',
            status: 'approved'
        );

        /** @var Conversion $conversion1 */
        $conversion1 = $result['conversion'];

        // Verify initial snapshot for Conversion #1001
        $snapshot1 = PayoutSnapshot::where('conversion_id', $conversion1->id)->first();
        $this->assertEquals(60.00, (float) $snapshot1->customer_payout);
        $this->assertEquals(40.00, (float) $snapshot1->affiliate_commission);

        // Later: Link is modified or new Link 2 created with Customer = ₹80, Affiliate Commission = ₹20
        $link1->update([
            'customer_payout' => 80.00,
            'affiliate_commission' => 20.00,
        ]);

        // Campaign default payout updated to ₹150
        $campaign->update([
            'advertiser_payout' => 150.00,
            'default_affiliate_payout' => 120.00,
        ]);

        // Verify Conversion #1001 MUST STILL REMAIN: Customer = ₹60, Affiliate Commission = ₹40!
        $freshSnapshot1 = PayoutSnapshot::where('conversion_id', $conversion1->id)->first();
        $this->assertEquals(60.00, (float) $freshSnapshot1->customer_payout);
        $this->assertEquals(40.00, (float) $freshSnapshot1->affiliate_commission);
        $this->assertEquals(100.00, (float) $freshSnapshot1->advertiser_payout);
    }
}
