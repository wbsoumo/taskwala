<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postback_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('auth_method', ['shared_secret', 'api_key', 'hmac_signature', 'provider_token', 'ip_only'])->default('shared_secret');
            $table->text('secret_key')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('postback_ip_whitelists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postback_provider_id')->constrained('postback_providers')->cascadeOnDelete();
            $table->string('ip_address', 45); // IPv4, IPv6 or CIDR
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('conversions', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('click_id', 64)->index();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('link_id')->constrained('affiliate_links')->cascadeOnDelete();
            $table->foreignId('postback_provider_id')->nullable()->constrained('postback_providers')->nullOnDelete();
            $table->string('provider_conversion_id')->nullable()->index();
            $table->enum('status', ['pending', 'approved', 'rejected', 'reversed', 'cancelled', 'paid'])->default('pending');
            $table->dateTime('conversion_time');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('payout_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversion_id')->unique()->constrained('conversions')->cascadeOnDelete();
            $table->decimal('advertiser_payout', 15, 2);
            $table->decimal('affiliate_allocated_payout', 15, 2);
            $table->decimal('customer_payout', 15, 2);
            $table->decimal('affiliate_commission', 15, 2);
            $table->decimal('platform_margin', 15, 2);
            $table->char('currency', 3)->default('INR');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('customer_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversion_id')->unique()->constrained('conversions')->cascadeOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('upi_id')->nullable();
            $table->string('upi_holder_name')->nullable();
            $table->string('mobile_number')->nullable();
            $table->decimal('payout_amount', 15, 2);
            $table->enum('status', ['pending', 'processed', 'failed'])->default('pending');
            $table->timestamps();
        });

        Schema::create('conversion_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversion_id')->constrained('conversions')->cascadeOnDelete();
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->enum('changed_by_type', ['admin', 'system', 'postback'])->default('system');
            $table->unsignedBigInteger('changed_by_id')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversion_status_histories');
        Schema::dropIfExists('customer_payouts');
        Schema::dropIfExists('payout_snapshots');
        Schema::dropIfExists('conversions');
        Schema::dropIfExists('postback_ip_whitelists');
        Schema::dropIfExists('postback_providers');
    }
};
