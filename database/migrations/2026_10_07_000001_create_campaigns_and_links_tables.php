<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('short_description')->nullable();
            $table->string('advertiser_name');
            $table->string('category');
            $table->string('campaign_type')->default('cpa');
            $table->text('landing_url');
            $table->string('conversion_event')->default('account_opening');
            $table->decimal('advertiser_payout', 15, 2);
            $table->decimal('default_affiliate_payout', 15, 2);
            $table->char('currency', 3)->default('INR');
            $table->enum('status', ['draft', 'active', 'paused', 'expired', 'archived'])->default('draft');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->text('terms')->nullable();
            $table->text('kpi_requirements')->nullable();
            $table->text('duplicate_conversion_rules')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('campaign_affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('affiliate_payout', 15, 2);
            $table->enum('status', ['allowed', 'blocked'])->default('allowed');
            $table->timestamps();
            $table->unique(['campaign_id', 'user_id']);
        });

        Schema::create('affiliate_links', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('secure_token', 64)->unique();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('allocated_affiliate_payout', 15, 2);
            $table->decimal('customer_payout', 15, 2);
            $table->decimal('affiliate_commission', 15, 2);
            $table->enum('status', ['active', 'disabled'])->default('active');
            $table->bigInteger('click_count')->default(0);
            $table->bigInteger('conversion_count')->default(0);
            $table->timestamps();
        });

        Schema::create('clicks', function (Blueprint $table) {
            $table->id();
            $table->string('click_id', 64)->unique();
            $table->foreignId('link_id')->constrained('affiliate_links')->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('allocated_affiliate_payout', 15, 2);
            $table->decimal('customer_payout', 15, 2);
            $table->decimal('affiliate_commission', 15, 2);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->text('referrer')->nullable();
            $table->string('device_type')->nullable();
            $table->string('os')->nullable();
            $table->string('browser')->nullable();
            $table->enum('status', ['tracked', 'invalid', 'converted'])->default('tracked');
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clicks');
        Schema::dropIfExists('affiliate_links');
        Schema::dropIfExists('campaign_affiliates');
        Schema::dropIfExists('campaigns');
    }
};
