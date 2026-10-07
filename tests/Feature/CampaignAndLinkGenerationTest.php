<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CampaignAndLinkGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_attack_manipulated_frontend_payout_and_user_id_ignored()
    {
        $userA = User::create([
            'name' => 'Victim User A',
            'email' => 'usera@test.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $attacker = User::create([
            'name' => 'Attacker User B',
            'email' => 'attacker@test.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $campaign = Campaign::create([
            'name' => 'Test Campaign',
            'slug' => 'test-campaign',
            'advertiser_name' => 'Advertiser A',
            'category' => 'Finance',
            'landing_url' => 'https://example.com?click_id={click_id}',
            'conversion_event' => 'account_opening',
            'advertiser_payout' => 100.00,
            'default_affiliate_payout' => 100.00,
            'currency' => 'INR',
            'status' => 'active',
        ]);

        // Attacker logs in
        $this->actingAs($attacker, 'web');

        // Attacker attempts malicious payload to claim ₹100,000 payout and spoof user_id
        $response = $this->post(route('user.links.generate'), [
            'campaign_id' => $campaign->id,
            'affiliate_payout' => 100000.00,
            'customer_payout' => 10.00,
            'user_id' => $userA->id, // Attempting to generate on behalf of User A
        ]);

        $response->assertRedirect(route('user.links.index'));

        // Verify in DB that the link belongs strictly to the authenticated attacker, and payout is capped to DB record (₹100)
        $this->assertDatabaseHas('affiliate_links', [
            'campaign_id' => $campaign->id,
            'user_id' => $attacker->id, // Bound to authenticated user!
            'allocated_affiliate_payout' => 100.00, // Pulled from DB, NOT 100,000!
            'customer_payout' => 10.00,
            'affiliate_commission' => 90.00, // 100 - 10
        ]);
    }
}
