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
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->text('original_url');
            $table->text('affiliate_url')->nullable();
            $table->string('url_hash', 64)->index();
            $table->string('source_channel', 150)->default('manual')->index();
            $table->string('source_message_id', 100)->nullable()->index();
            $table->text('image_url')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('original_price', 10, 2)->nullable();
            $table->integer('discount_percent')->nullable();
            $table->string('commission_rate', 100)->nullable();
            $table->string('category', 100)->default('General')->index();
            $table->enum('status', ['active', 'expired', 'hidden'])->default('active')->index();
            $table->enum('conversion_status', ['converted', 'pending', 'failed'])->default('pending')->index();
            $table->text('conversion_error')->nullable();
            $table->unsignedInteger('clicks_count')->default(0);
            $table->timestamp('posted_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('deal_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 150)->default('all');
            $table->string('status', 50)->default('success'); // success, partial, failed
            $table->integer('deals_found')->default(0);
            $table->integer('deals_added')->default(0);
            $table->integer('deals_failed')->default(0);
            $table->text('error_message')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deal_sync_logs');
        Schema::dropIfExists('deals');
    }
};
