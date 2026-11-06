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
        // 1. Connected Telegram MTProto Accounts
        Schema::create('telegram_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->unique();
            $table->string('api_id')->nullable();
            $table->string('api_hash')->nullable();
            $table->longText('session_string')->nullable();
            $table->bigInteger('telegram_user_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->nullable();
            $table->boolean('is_connected')->default(false);
            $table->string('status')->default('disconnected'); // disconnected, code_sent, 2fa_required, connected, error
            $table->string('phone_code_hash')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamps();
        });

        // 2. Monitored Telegram Channels & Groups
        Schema::create('telegram_monitored_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_account_id')->nullable()->constrained('telegram_accounts')->nullOnDelete();
            $table->bigInteger('channel_id')->unique(); // Telegram ID e.g. -1001234567890
            $table->string('access_hash')->nullable();
            $table->string('title');
            $table->string('username')->nullable(); // e.g. taskbazi_loot
            $table->string('type')->default('channel'); // channel, supergroup, group, chat
            $table->integer('participants_count')->nullable();
            $table->boolean('is_monitored')->default(false);
            $table->string('sync_status')->default('idle'); // idle, syncing, synced, error
            $table->integer('historical_limit')->default(100);
            $table->integer('historical_synced_count')->default(0);
            $table->integer('total_messages_imported')->default(0);
            $table->integer('total_deals_imported')->default(0);
            $table->timestamp('last_scraped_at')->nullable();
            $table->bigInteger('last_message_id')->nullable();
            $table->timestamps();

            $table->index(['is_monitored', 'sync_status']);
        });

        // 3. Raw & Scraped Telegram Messages
        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('channel_id')->index();
            $table->bigInteger('message_id')->index();
            $table->bigInteger('sender_id')->nullable();
            $table->longText('text')->nullable();
            $table->string('media_type')->nullable(); // photo, video, document, web_page, none
            $table->text('media_url')->nullable();
            $table->text('media_local_path')->nullable();
            $table->json('extracted_urls')->nullable();
            $table->boolean('is_deal')->default(false);
            $table->foreignId('deal_id')->nullable()->constrained('deals')->nullOnDelete();
            $table->boolean('is_edited')->default(false);
            $table->json('raw_payload')->nullable();
            $table->timestamp('posted_at');
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();

            $table->unique(['channel_id', 'message_id']);
            $table->index('posted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');
        Schema::dropIfExists('telegram_monitored_channels');
        Schema::dropIfExists('telegram_accounts');
    }
};
