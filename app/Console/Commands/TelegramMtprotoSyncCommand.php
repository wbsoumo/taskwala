<?php

namespace App\Console\Commands;

use App\Models\TelegramAccount;
use App\Models\TelegramMonitoredChannel;
use App\Services\TelegramMtproto\TelethonClientService;
use App\Services\TelegramMtproto\TelegramMessageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramMtprotoSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:mtproto-sync 
                            {--limit=25 : Number of recent messages to scrape per channel}
                            {--channel= : Specific channel ID to scrape}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape latest deals and messages from monitored Telegram channels using Telethon (cPanel Cron friendly)';

    protected TelethonClientService $telethonService;
    protected TelegramMessageProcessor $messageProcessor;

    public function __construct(
        TelethonClientService $telethonService,
        TelegramMessageProcessor $messageProcessor
    ) {
        parent::__construct();
        $this->telethonService = $telethonService;
        $this->messageProcessor = $messageProcessor;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('[' . now()->toDateTimeString() . '] Starting Telegram MTProto Deal Sync...');

        $account = TelegramAccount::where('is_connected', true)->first();

        if (!$account || empty($account->session_string)) {
            $this->warn('No connected Telegram account found with a valid session string.');
            return self::SUCCESS;
        }

        $channelsQuery = TelegramMonitoredChannel::where('is_monitored', true);

        if ($this->option('channel')) {
            $channelsQuery->where('channel_id', $this->option('channel'));
        }

        $channels = $channelsQuery->get();

        if ($channels->isEmpty()) {
            $this->comment('No channels marked for monitoring. Select channels in Admin > Telegram MTProto to enable.');
            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $totalProcessed = 0;
        $totalDeals = 0;

        foreach ($channels as $channel) {
            $this->line("-> Scraping '{$channel->title}' (ID: {$channel->channel_id}, limit: {$limit})...");

            try {
                $channel->update(['sync_status' => 'syncing']);

                $res = $this->telethonService->syncChannelHistory(
                    channelId: $channel->channel_id,
                    limit: $limit,
                    sessionString: $account->session_string,
                    apiId: $account->api_id,
                    apiHash: $account->api_hash
                );

                if ($res['success'] ?? false) {
                    $messages = $res['messages'] ?? [];
                    $importedCount = $res['messages_imported'] ?? count($messages);
                    $dealsFound = $res['deals_found'] ?? 0;

                    // Ensure messages are processed into DB
                    $procResult = $res['processed_result'] ?? null;
                    if (!$procResult && !empty($messages)) {
                        $procResult = $this->messageProcessor->processPayload([
                            'batch' => true,
                            'messages' => $messages,
                        ]);
                    }
                    if ($procResult) {
                        $totalProcessed += ($procResult['messages_processed'] ?? 0);
                        $totalDeals += ($procResult['deals_created'] ?? 0);
                    }

                    $channel->update([
                        'sync_status' => 'synced',
                        'last_scraped_at' => now(),
                        'total_messages_count' => $channel->messages()->count(),
                        'total_deals_count' => $channel->messages()->where('is_deal', true)->count(),
                    ]);

                    $this->info("   Done: {$importedCount} messages fetched, {$dealsFound} deals identified.");
                } else {
                    $error = $res['error'] ?? 'Unknown error';
                    $this->error("   Failed to sync '{$channel->title}': {$error}");
                    $channel->update(['sync_status' => 'error']);
                }
            } catch (\Throwable $e) {
                Log::error("telegram:mtproto-sync exception for channel {$channel->channel_id}: " . $e->getMessage());
                $this->error("   Exception: " . $e->getMessage());
                $channel->update(['sync_status' => 'error']);
            }
        }

        $this->info('[' . now()->toDateTimeString() . "] Telegram MTProto Deal Sync finished. Processed {$totalProcessed} messages, created {$totalDeals} new deals.");

        return self::SUCCESS;
    }
}
