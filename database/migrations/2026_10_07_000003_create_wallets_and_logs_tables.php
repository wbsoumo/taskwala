<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->decimal('pending_balance', 15, 2)->default(0.00);
            $table->decimal('total_withdrawn', 15, 2)->default(0.00);
            $table->char('currency', 3)->default('INR');
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('conversion_id')->nullable()->constrained('conversions')->nullOnDelete();
            $table->enum('type', [
                'conversion_earning', 
                'customer_reward', 
                'affiliate_commission', 
                'payout_request', 
                'payout_reversal', 
                'adjustment'
            ]);
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['completed', 'pending', 'cancelled'])->default('completed');
            $table->timestamps();
        });

        Schema::create('postback_logs', function (Blueprint $table) {
            $table->id();
            $table->string('request_id', 64)->index();
            $table->foreignId('postback_provider_id')->nullable()->constrained('postback_providers')->nullOnDelete();
            $table->string('endpoint');
            $table->string('source_ip', 45);
            $table->string('http_method', 10);
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->boolean('auth_result')->default(false);
            $table->boolean('ip_whitelist_result')->default(false);
            $table->boolean('click_validation_result')->default(false);
            $table->boolean('conversion_result')->default(false);
            $table->string('rejection_reason')->nullable();
            $table->integer('response_code')->default(200);
            $table->integer('processing_time_ms')->default(0);
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('actor_type', ['admin', 'user', 'system'])->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action', 100);
            $table->string('entity_type', 100)->nullable();
            $table->string('entity_id', 100)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->string('guard', 20);
            $table->string('email');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('status', ['success', 'failed', 'blocked'])->default('failed');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->default('general');
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('login_logs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('postback_logs');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
