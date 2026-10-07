<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\PostbackProvider;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo affiliate user
        $user = User::updateOrCreate(
            ['email' => 'affiliate@platform.com'],
            [
                'name' => 'John Affiliate',
                'mobile_number' => '9876543210',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'upi_id' => 'john@upi',
                'upi_holder_name' => 'John Affiliate',
            ]
        );

        // Create demo postback provider
        $provider = PostbackProvider::updateOrCreate(
            ['slug' => 'network-a'],
            [
                'name' => 'Network A',
                'auth_method' => 'shared_secret',
                'secret_key' => 'secret_token_12345',
                'status' => 'active',
            ]
        );

        $provider->ipWhitelists()->updateOrCreate(
            ['ip_address' => '127.0.0.1'],
            ['description' => 'Localhost testing']
        );

        // Create demo campaign
        Campaign::updateOrCreate(
            ['slug' => 'kotak-811-account'],
            [
                'name' => 'Kotak 811 Savings Account',
                'description' => 'Zero balance digital savings account opening campaign.',
                'short_description' => 'Earn up to ₹100 per successful account opening.',
                'advertiser_name' => 'Kotak Mahindra Bank',
                'category' => 'Banking',
                'campaign_type' => 'cpa',
                'landing_url' => 'https://advertiser.com/landing?click_id={click_id}',
                'conversion_event' => 'account_opening',
                'advertiser_payout' => 100.00,
                'default_affiliate_payout' => 100.00,
                'currency' => 'INR',
                'status' => 'active',
                'terms' => 'Minimum deposit of ₹500 required for conversion approval.',
                'kpi_requirements' => 'Aadhaar linked mobile number mandatory.',
            ]
        );
    }
}
