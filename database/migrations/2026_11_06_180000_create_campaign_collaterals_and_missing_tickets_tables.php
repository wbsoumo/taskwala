<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Campaign Collaterals Table (Marketing posters, banners, and videos)
        if (!Schema::hasTable('campaign_collaterals')) {
            Schema::create('campaign_collaterals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
                $table->string('title');
                $table->string('media_type')->default('image'); // 'image' or 'video'
                $table->string('file_path');
                $table->string('thumbnail_path')->nullable();
                $table->text('default_caption')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['campaign_id', 'is_active', 'sort_order']);
            });
        }

        // 2. Missing Conversion Tickets Table (Dispute and claim system)
        if (!Schema::hasTable('missing_conversion_tickets')) {
            Schema::create('missing_conversion_tickets', function (Blueprint $table) {
                $table->id();
                $table->uuid('public_id')->unique();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
                $table->string('click_id')->nullable();
                $table->string('order_reference_id');
                $table->date('task_date');
                $table->string('screenshot_path')->nullable();
                $table->text('notes')->nullable();
                $table->string('status')->default('pending'); // 'pending', 'under_review', 'approved', 'rejected'
                $table->text('admin_notes')->nullable();
                $table->decimal('approved_payout', 10, 2)->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('admins')->nullOnDelete();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['campaign_id', 'status']);
                $table->index('order_reference_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('missing_conversion_tickets');
        Schema::dropIfExists('campaign_collaterals');
    }
};
