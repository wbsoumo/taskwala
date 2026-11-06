<?php

namespace App\Services\TelegramMtproto;

use App\Models\Deal;
use App\Models\TelegramMessage;
use App\Models\TelegramMonitoredChannel;
use App\Services\Deals\DealAggregatorService;
use App\Services\Deals\TelegramDealService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TelegramMessageProcessor
{
    protected TelegramDealService $telegramDealService;
    protected DealAggregatorService $dealAggregatorService;

    public function __construct(
        TelegramDealService $telegramDealService,
        DealAggregatorService $dealAggregatorService
    ) {
        $this->telegramDealService = $telegramDealService;
        $this->dealAggregatorService = $dealAggregatorService;
    }

    /**
     * Process an incoming message or batch of messages from Telethon.
     */
    public function processPayload(array $payload): array
    {
        if (isset($payload['batch']) && is_array($payload['messages'])) {
            $processed = 0;
            $dealsCount = 0;

            foreach ($payload['messages'] as $msg) {
                $res = $this->processSingleMessage($msg);
                if ($res['processed'] ?? false) {
                    $processed++;
                }
                if ($res['is_deal'] ?? false) {
                    $dealsCount++;
                }
            }

            return [
                'success' => true,
                'batch' => true,
                'messages_processed' => $processed,
                'deals_created' => $dealsCount,
            ];
        }

        return $this->processSingleMessage($payload);
    }

    /**
     * Process a single message payload.
     */
    public function processSingleMessage(array $data): array
    {
        $channelId = (int) ($data['channel_id'] ?? 0);
        $messageId = (int) ($data['message_id'] ?? 0);
        $text = trim($data['text'] ?? '');
        $mediaType = $data['media_type'] ?? 'none';
        $mediaPath = $data['media_local_path'] ?? null;
        $urls = $data['extracted_urls'] ?? [];
        $postedAt = isset($data['posted_at']) ? Carbon::parse($data['posted_at']) : now();
        $isEdited = (bool) ($data['is_edited'] ?? false);
        $editedAt = isset($data['edited_at']) ? Carbon::parse($data['edited_at']) : null;

        if (!$channelId || !$messageId) {
            return ['processed' => false, 'reason' => 'Missing channel_id or message_id'];
        }

        // Find or check monitored channel
        $channel = TelegramMonitoredChannel::where('channel_id', $channelId)->first();
        $channelName = $channel ? ($channel->username ? '@' . $channel->username : $channel->title) : (string) $channelId;

        // Check if message already exists
        $existing = TelegramMessage::where('channel_id', $channelId)
            ->where('message_id', $messageId)
            ->first();

        if ($existing && !$isEdited) {
            return ['processed' => false, 'duplicate' => true];
        }

        // Detect if this is an e-commerce / deal message
        $isDeal = false;
        $dealId = $existing?->deal_id;
        $validUrl = reset($urls) ?: null;

        if ($validUrl && $this->telegramDealService->isValidDealUrl($validUrl)) {
            $isDeal = true;
            $imageUrl = $mediaPath ? asset('storage/' . $mediaPath) : null;

            // Build deal payload
            $dealPayload = $this->telegramDealService->buildDealPayload(
                $text,
                $validUrl,
                $channelName,
                (string) $messageId,
                $imageUrl,
                $postedAt
            );

            // Store or update deal
            $dealResult = $this->dealAggregatorService->processAndStoreDeal($dealPayload);
            if (!empty($dealResult['deal'])) {
                $dealId = $dealResult['deal']->id;
            }
        }

        // Create or update TelegramMessage record
        if ($existing) {
            $existing->update([
                'text' => $text,
                'media_type' => $mediaType,
                'media_local_path' => $mediaPath ?: $existing->media_local_path,
                'extracted_urls' => $urls,
                'is_deal' => $isDeal,
                'deal_id' => $dealId,
                'is_edited' => true,
                'edited_at' => $editedAt ?: now(),
                'raw_payload' => $data['raw'] ?? $existing->raw_payload,
            ]);
            $msgRecord = $existing;
        } else {
            $msgRecord = TelegramMessage::create([
                'channel_id' => $channelId,
                'message_id' => $messageId,
                'sender_id' => $data['sender_id'] ?? null,
                'text' => $text,
                'media_type' => $mediaType,
                'media_local_path' => $mediaPath,
                'extracted_urls' => $urls,
                'is_deal' => $isDeal,
                'deal_id' => $dealId,
                'is_edited' => false,
                'raw_payload' => $data['raw'] ?? [],
                'posted_at' => $postedAt,
            ]);

            // Update channel counters
            if ($channel) {
                $channel->increment('total_messages_imported');
                if ($isDeal) {
                    $channel->increment('total_deals_imported');
                }
                $channel->update([
                    'last_scraped_at' => now(),
                    'last_message_id' => max($channel->last_message_id ?? 0, $messageId),
                ]);
            }
        }

        return [
            'processed' => true,
            'message_id' => $messageId,
            'is_deal' => $isDeal,
            'deal_id' => $dealId,
        ];
    }
}
