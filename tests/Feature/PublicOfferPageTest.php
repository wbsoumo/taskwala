<?php

namespace Tests\Feature;

use App\Models\AffiliateLink;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicOfferPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_customer_payout_redirects_directly_to_advertiser_url()
    {
        $user = User::create([
            'name' => 'Affiliate A',
            'email' => 'user@test.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $campaign = Campaign::create([
            'name' => 'Direct Campaign',
            'slug' => 'direct-campaign',
            'advertiser_name' => 'Advertiser A',
            'category' => 'Banking',
            'landing_url' => 'https://advertiser.com/target?click_id={click_id}',
            'conversion_event' => 'lead',
            'advertiser_payout' => 100.00,
            'default_affiliate_payout' => 100.00,
            'currency' => 'INR',
            'status' => 'active',
        ]);

        $link = AffiliateLink::create([
            'secure_token' => 'token_direct_redirect',
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'allocated_affiliate_payout' => 100.00,
            'customer_payout' => 0.00, // ₹0 customer payout -> Direct redirect!
            'affiliate_commission' => 100.00,
            'status' => 'active',
        ]);

        $response = $this->get('/go/token_direct_redirect');

        $response->assertRedirect();
        $this->assertStringContainsString('https://advertiser.com/target?click_id=CLK_', $response->headers->get('Location'));
    }

    public function test_positive_customer_payout_shows_public_offer_page()
    {
        $user = User::create([
            'name' => 'Affiliate B',
            'email' => 'userb@test.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $campaign = Campaign::create([
            'name' => 'Kotak 811 Account',
            'slug' => 'kotak-811',
            'advertiser_name' => 'Kotak Bank',
            'category' => 'Banking',
            'landing_url' => 'https://advertiser.com/kotak?click_id={click_id}',
            'conversion_event' => 'account_opening',
            'advertiser_payout' => 100.00,
            'default_affiliate_payout' => 100.00,
            'currency' => 'INR',
            'status' => 'active',
            'theme' => 'dark_glass',
        ]);

        $link = AffiliateLink::create([
            'secure_token' => 'token_with_customer_reward',
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'allocated_affiliate_payout' => 100.00,
            'customer_payout' => 60.00, // ₹60 customer reward
            'affiliate_commission' => 40.00,
            'status' => 'active',
        ]);

        $response = $this->get('/go/token_with_customer_reward');

        $response->assertStatus(200);
        $response->assertSee('Kotak 811 Account');
        $response->assertSee('₹60.00');
        $response->assertSee('taskwala.co.in');
    }

    public function test_valid_upi_submission_creates_payout_record_and_redirects()
    {
        $user = User::create([
            'name' => 'Affiliate C',
            'email' => 'userc@test.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $campaign = Campaign::create([
            'name' => 'SBI Card Offer',
            'slug' => 'sbi-card',
            'advertiser_name' => 'SBI Card',
            'category' => 'Credit Card',
            'landing_url' => 'https://advertiser.com/sbi?click_id={click_id}',
            'conversion_event' => 'card_approval',
            'advertiser_payout' => 500.00,
            'default_affiliate_payout' => 500.00,
            'currency' => 'INR',
            'status' => 'active',
        ]);

        $link = AffiliateLink::create([
            'secure_token' => 'token_upi_test',
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'allocated_affiliate_payout' => 500.00,
            'customer_payout' => 300.00,
            'affiliate_commission' => 200.00,
            'status' => 'active',
        ]);

        $response = $this->post('/go/token_upi_test/submit', [
            'upi_id' => 'john.doe@ybl',
            'upi_holder_name' => 'John Doe',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('customer_payouts', [
            'link_id' => $link->id,
            'upi_id' => 'john.doe@ybl',
            'payout_amount' => 300.00,
            'status' => 'pending',
        ]);
    }
}
