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

        // 1. Banking Campaign
        Campaign::updateOrCreate(
            ['slug' => 'kotak-811-account'],
            [
                'name' => 'Kotak 811 Savings Account',
                'description' => 'Zero balance digital savings account opening campaign.',
                'short_description' => 'Open zero-balance digital savings account in 5 minutes.',
                'advertiser_name' => 'Kotak Mahindra Bank',
                'category' => 'Banking',
                'campaign_type' => 'cpa',
                'landing_url' => 'https://kotak.com/811?click_id={click_id}',
                'conversion_event' => 'account_opening',
                'advertiser_payout' => 150.00,
                'default_affiliate_payout' => 120.00,
                'currency' => 'INR',
                'status' => 'active',
                'theme' => 'gradient_blue',
                'check_duplicate_ip' => true,
                'terms' => 'Aadhaar linked mobile number and initial funding required.',
                'kpi_requirements' => 'Valid PAN and Aadhaar OTP verification required.',
            ]
        );

        // 2. Demat Campaign
        Campaign::updateOrCreate(
            ['slug' => 'angel-one-demat'],
            [
                'name' => 'Angel One Free Demat Account',
                'description' => 'Open free Demat & Trading account with zero brokerage on equity delivery.',
                'short_description' => 'Zero account opening fee + ₹20 per trade for intraday.',
                'advertiser_name' => 'Angel One Ltd',
                'category' => 'Demat',
                'campaign_type' => 'cpa',
                'landing_url' => 'https://angelone.in/open-demat?click_id={click_id}',
                'conversion_event' => 'account_opening',
                'advertiser_payout' => 300.00,
                'default_affiliate_payout' => 250.00,
                'currency' => 'INR',
                'status' => 'active',
                'theme' => 'dark_glass',
                'check_duplicate_ip' => true,
                'terms' => 'Successful KYC submission and mobile verification required.',
                'kpi_requirements' => 'Age 18+ with active bank account in India.',
            ]
        );

        // 3. Credit Card Campaign
        Campaign::updateOrCreate(
            ['slug' => 'sbi-simplyclick-card'],
            [
                'name' => 'SBI SimplyCLICK Credit Card',
                'description' => 'Earn 10X reward points on online shopping with Amazon, BookMyShow, Cleartrip.',
                'short_description' => 'Instant approval credit card for salaried and self-employed.',
                'advertiser_name' => 'SBI Cards',
                'category' => 'Credit Card',
                'campaign_type' => 'cpa',
                'landing_url' => 'https://sbicard.com/simplyclick?click_id={click_id}',
                'conversion_event' => 'card_approval',
                'advertiser_payout' => 500.00,
                'default_affiliate_payout' => 450.00,
                'currency' => 'INR',
                'status' => 'active',
                'theme' => 'gradient_blue',
                'check_duplicate_ip' => true,
                'terms' => 'Income proof and credit score above 720 required for instant approval.',
                'kpi_requirements' => 'Monthly income ₹25,000+ or existing credit card holder.',
            ]
        );

        // 4. Loan Campaign
        Campaign::updateOrCreate(
            ['slug' => 'hdfc-personal-loan'],
            [
                'name' => 'HDFC Instant Personal Loan',
                'description' => 'Get up to ₹40 Lakhs personal loan with interest rates starting at 10.5% p.a.',
                'short_description' => 'Paperless loan disbursal within 24 hours of approval.',
                'advertiser_name' => 'HDFC Bank',
                'category' => 'Loan',
                'campaign_type' => 'cpa',
                'landing_url' => 'https://hdfcbank.com/personal-loan?click_id={click_id}',
                'conversion_event' => 'loan_disbursal',
                'advertiser_payout' => 750.00,
                'default_affiliate_payout' => 600.00,
                'currency' => 'INR',
                'status' => 'active',
                'theme' => 'emerald',
                'check_duplicate_ip' => true,
                'terms' => 'Minimum 6 months bank statement and KYC documentation needed.',
                'kpi_requirements' => 'Salaried individuals with clean CIBIL history.',
            ]
        );
    }
}
