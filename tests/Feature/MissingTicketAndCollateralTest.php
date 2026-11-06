<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Campaign;
use App\Models\MissingConversionTicket;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MissingTicketAndCollateralTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_missing_conversion_ticket(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $wallet = $user->wallet ?: Wallet::create(['user_id' => $user->id, 'balance' => 0.00]);

        $campaign = Campaign::create([
            'name' => 'Kotak 811 Zero Balance',
            'advertiser_name' => 'Kotak Bank',
            'category' => 'Banking',
            'landing_url' => 'https://kotak.com',
            'conversion_event' => 'Account Open',
            'advertiser_payout' => 300.00,
            'default_affiliate_payout' => 200.00,
            'currency' => 'INR',
            'status' => 'active',
        ]);

        $file = UploadedFile::fake()->image('screenshot.png');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tickets', [
            'campaign_id' => $campaign->id,
            'order_reference_id' => 'KOTAK-REF-998811',
            'task_date' => '2026-11-06',
            'notes' => 'Completed full video KYC and deposited ₹1000.',
            'screenshot' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'ticket' => [
                    'status' => 'pending',
                    'order_reference_id' => 'KOTAK-REF-998811',
                ],
            ]);

        $this->assertDatabaseHas('missing_conversion_tickets', [
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'order_reference_id' => 'KOTAK-REF-998811',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_approve_missing_ticket_and_credit_wallet(): void
    {
        $admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $user = User::factory()->create(['status' => 'active']);
        $wallet = $user->wallet ?: Wallet::create(['user_id' => $user->id, 'balance' => 50.00]);
        $wallet->update(['balance' => 50.00]);

        $campaign = Campaign::create([
            'name' => 'Amazon Prime Offer',
            'advertiser_name' => 'Amazon',
            'category' => 'Subscription',
            'landing_url' => 'https://amazon.in',
            'conversion_event' => 'Subscription',
            'advertiser_payout' => 150.00,
            'default_affiliate_payout' => 100.00,
            'currency' => 'INR',
            'status' => 'active',
        ]);

        $ticket = MissingConversionTicket::create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'order_reference_id' => 'AMZ-12345',
            'task_date' => '2026-11-05',
            'status' => 'pending',
        ]);

        $response = $this->withoutExceptionHandling()
            ->actingAs($admin, 'admin')->post("/admin/tickets/{$ticket->id}/status", [
            'status' => 'approved',
            'approved_payout' => 100.00,
            'admin_notes' => 'Verified with Amazon reporting. Credited.',
        ]);

        dump($response->headers->get('Location'));
        $response->assertStatus(302);

        $ticket->refresh();
        $this->assertEquals('approved', $ticket->status);
        $this->assertEquals(100.00, (float) $ticket->approved_payout);

        $wallet->refresh();
        $this->assertEquals(150.00, (float) $wallet->balance);
    }
}
