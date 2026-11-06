<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AffiliateLink;
use App\Models\Campaign;
use App\Models\CustomerPayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCustomerPayoutsTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected User $user;
    protected Campaign $campaign;
    protected AffiliateLink $link;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Admin Controller',
            'email' => 'admin@taskwala.com',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);

        $this->user = User::create([
            'name' => 'Affiliate Hero',
            'email' => 'affiliate@taskwala.com',
            'mobile_number' => '9888877777',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->campaign = Campaign::create([
            'public_id' => 'CMP_TEST',
            'name' => 'Kotak Savings Account',
            'slug' => 'kotak-savings-account',
            'category' => 'Banking',
            'advertiser_name' => 'Kotak',
            'landing_url' => 'https://kotak.com?click_id={click_id}',
            'conversion_event' => 'account_opened',
            'advertiser_payout' => 500.00,
            'default_affiliate_payout' => 300.00,
            'currency' => 'INR',
            'status' => 'active',
        ]);

        $this->link = AffiliateLink::create([
            'user_id' => $this->user->id,
            'campaign_id' => $this->campaign->id,
            'secure_token' => 'secure_sample_token_40_chars_long_12345',
            'allocated_affiliate_payout' => 300.00,
            'customer_payout' => 50.00,
            'affiliate_commission' => 250.00,
        ]);
    }

    public function test_admin_can_view_customer_payouts_page_with_filters()
    {
        CustomerPayout::create([
            'link_id' => $this->link->id,
            'upi_id' => 'customer1@okhdfcbank',
            'upi_holder_name' => 'Rajesh Sharma',
            'payout_amount' => 50.00,
            'status' => 'pending',
            'created_at' => now()->subDays(2),
        ]);

        CustomerPayout::create([
            'link_id' => $this->link->id,
            'upi_id' => 'customer2@okaxis',
            'upi_holder_name' => 'Priya Patel',
            'payout_amount' => 100.00,
            'status' => 'done',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.finance.customer_payouts'));

        $response->assertStatus(200);
        $response->assertSee('customer1@okhdfcbank');
        $response->assertSee('customer2@okaxis');

        // Test status filter
        $responsePending = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.finance.customer_payouts', ['status' => 'pending']));
        $responsePending->assertSee('customer1@okhdfcbank');
        $responsePending->assertDontSee('customer2@okaxis');

        // Test search filter
        $responseSearch = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.finance.customer_payouts', ['search' => 'Priya']));
        $responseSearch->assertSee('Priya Patel');
        $responseSearch->assertDontSee('Rajesh Sharma');
    }

    public function test_admin_can_update_single_customer_payout_status()
    {
        $payout = CustomerPayout::create([
            'link_id' => $this->link->id,
            'upi_id' => 'raj@ybl',
            'upi_holder_name' => 'Raj',
            'payout_amount' => 60.00,
            'status' => 'pending',
        ]);

        // Update to initiated
        $response1 = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.finance.customer_payouts.update_status', $payout->id), [
                'status' => 'initiated',
                'admin_notes' => 'Payout initiated via batch processing.',
            ]);

        $response1->assertRedirect();
        $this->assertDatabaseHas('customer_payouts', [
            'id' => $payout->id,
            'status' => 'initiated',
            'admin_notes' => 'Payout initiated via batch processing.',
        ]);

        // Update to done with transaction ID
        $response2 = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.finance.customer_payouts.update_status', $payout->id), [
                'status' => 'done',
                'transaction_id' => 'UTR123456789',
                'admin_notes' => 'Transferred successfully.',
            ]);

        $response2->assertRedirect();
        $this->assertDatabaseHas('customer_payouts', [
            'id' => $payout->id,
            'status' => 'done',
            'transaction_id' => 'UTR123456789',
        ]);

        // Update to failed
        $response3 = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.finance.customer_payouts.update_status', $payout->id), [
                'status' => 'failed',
                'admin_notes' => 'UPI ID is inactive.',
            ]);

        $response3->assertRedirect();
        $this->assertDatabaseHas('customer_payouts', [
            'id' => $payout->id,
            'status' => 'failed',
        ]);

        // Update to cancel
        $response4 = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.finance.customer_payouts.update_status', $payout->id), [
                'status' => 'cancel',
                'admin_notes' => 'Cancelled by admin.',
            ]);

        $response4->assertRedirect();
        $this->assertDatabaseHas('customer_payouts', [
            'id' => $payout->id,
            'status' => 'cancel',
        ]);
    }

    public function test_admin_can_bulk_update_customer_payouts()
    {
        $payout1 = CustomerPayout::create([
            'link_id' => $this->link->id,
            'upi_id' => 'user1@upi',
            'payout_amount' => 50.00,
            'status' => 'pending',
        ]);

        $payout2 = CustomerPayout::create([
            'link_id' => $this->link->id,
            'upi_id' => 'user2@upi',
            'payout_amount' => 75.00,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.finance.customer_payouts.bulk_update'), [
                'selected_ids' => [$payout1->id, $payout2->id],
                'bulk_status' => 'done',
                'bulk_transaction_id' => 'BATCH_REF_9988',
                'bulk_admin_notes' => 'Bulk bank transfer.',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('customer_payouts', [
            'id' => $payout1->id,
            'status' => 'done',
            'transaction_id' => 'BATCH_REF_9988',
        ]);
        $this->assertDatabaseHas('customer_payouts', [
            'id' => $payout2->id,
            'status' => 'done',
            'transaction_id' => 'BATCH_REF_9988',
        ]);
    }
}
