<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add referral_code and referred_by to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'referral_code')) {
                $table->string('referral_code')->nullable()->unique()->after('mobile_number');
            }
            if (!Schema::hasColumn('users', 'referred_by')) {
                $table->foreignId('referred_by')->nullable()->after('referral_code')->constrained('users')->onDelete('set null');
            }
        });

        // 2. Referral Rules Table (Versioned)
        Schema::create('referral_rules', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->string('name');
            $table->unsignedInteger('version')->default(1);
            $table->enum('reward_type', ['fixed', 'percentage'])->default('percentage');
            $table->decimal('reward_value', 10, 2); // Fixed ₹ amount or % (e.g., 10.00 for 10%)
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->onDelete('set null');
            $table->string('category')->nullable();
            $table->enum('condition_type', ['first_approved_conversion', 'every_approved_conversion', 'registration'])->default('every_approved_conversion');
            $table->boolean('is_recurring')->default(true);
            $table->unsignedInteger('priority')->default(10);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();
        });

        // 3. Referral Earnings Ledger Table
        Schema::create('referral_earnings', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referred_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referral_rule_id')->nullable()->constrained('referral_rules')->onDelete('set null');
            $table->unsignedInteger('rule_version')->default(1);
            $table->foreignId('conversion_id')->nullable()->constrained('conversions')->onDelete('set null');
            $table->enum('reward_type', ['fixed', 'percentage']);
            $table->decimal('reward_amount', 12, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'reversed'])->default('approved');
            $table->string('reference')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['referrer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_earnings');
        Schema::dropIfExists('referral_rules');
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'referred_by')) {
                $table->dropForeign(['referred_by']);
                $table->dropColumn('referred_by');
            }
            if (Schema::hasColumn('users', 'referral_code')) {
                $table->dropColumn('referral_code');
            }
        });
    }
};
