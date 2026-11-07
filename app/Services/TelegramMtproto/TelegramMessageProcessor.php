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

        // Detect if this is an e-commerce / deal message
        $isDeal = false;
        $dealId = $existing?->deal_id;
        $dealCreated = false;

        // Check all extracted URLs for a valid deal link
        $validUrl = null;
        if (!empty($urls) && is_array($urls)) {
            foreach ($urls as $u) {
                if ($this->telegramDealService->isValidDealUrl($u)) {
                    $validUrl = $u;
                    break;
                }
            }
        }

        // Fallback: search text directly if extracted_urls was empty
        if (!$validUrl && !empty($text)) {
            $extractedFromText = $this->telegramDealService->extractUrlFromRawText($text);
            if ($extractedFromText && $this->telegramDealService->isValidDealUrl($extractedFromText)) {
                $validUrl = $extractedFromText;
                $urls[] = $validUrl;
            }
        }

        if ($validUrl) {
            $isDeal = true;
            $imageUrl = $mediaPath ? asset('storage/' . $mediaPath) : null;

            // Check if deal already exists in the deals table
            $dealExists = $dealId ? Deal::where('id', $dealId)->exists() : false;

            // If deal doesn't exist yet, or message was edited, process and store deal
            if (!$dealExists || $isEdited) {
                try {
                    $dealPayload = $this->telegramDealService->buildDealPayload(
                        $text,
                        $validUrl,
                        $channelName,
                        (string) $messageId,
                        $imageUrl,
                        $postedAt
                    );

                    $dealResult = $this->dealAggregatorService->processAndStoreDeal($dealPayload);
                    if (!empty($dealResult['deal'])) {
                        $dealId = $dealResult['deal']->id;
                        $dealCreated = true;
                    } elseif (!empty($dealResult['deal_id'])) {
                        $dealId = $dealResult['deal_id'];
                    }
                } catch (\Throwable $de) {
                    Log::warning("Failed to store deal for Telegram msg {$channelId}-{$messageId}: " . $de->getMessage());
                }
            }
        }

        // If message already exists and was not edited, skip updating TelegramMessage if already up to date
        if ($existing && !$isEdited && $existing->deal_id && $existing->is_deal == $isDeal) {
            return [
                'processed' => true,
                'duplicate' => true,
                'message_id' => $messageId,
                'is_deal' => $isDeal,
                'deal_id' => $dealId ?: $existing->deal_id,
                'deal_created' => $dealCreated,
            ];
        }

        // Create or update TelegramMessage record safely using updateOrCreate
        try {
            $msgRecord = TelegramMessage::updateOrCreate(
                [
                    'channel_id' => $channelId,
                    'message_id' => $messageId,
                ],
                [
                    'sender_id' => $data['sender_id'] ?? ($existing?->sender_id),
                    'text' => $text,
                    'media_type' => $mediaType,
                    'media_local_path' => $mediaPath ?: ($existing?->media_local_path),
                    'extracted_urls' => array_values(array_unique($urls)),
                    'is_deal' => $isDeal,
                    'deal_id' => $dealId ?: ($existing?->deal_id),
                    'is_edited' => $isEdited || ($existing?->is_edited ?? false),
                    'edited_at' => $isEdited ? ($editedAt ?: now()) : ($existing?->edited_at),
                    'raw_payload' => $data['raw'] ?? ($existing?->raw_payload ?? []),
                    'posted_at' => $postedAt,
                ]
            );

            // Update channel counters on new insertions
            if (!$existing && $channel) {
                $channel->increment('total_messages_imported');
                if ($isDeal) {
                    $channel->increment('total_deals_imported');
                }
                $channel->update([
                    'last_scraped_at' => now(),
                    'last_message_id' => max($channel->last_message_id ?? 0, $messageId),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("Error saving TelegramMessage [{$channelId}-{$messageId}]: " . $e->getMessage());
        }

        return [
            'processed' => true,
            'message_id' => $messageId,
            'is_deal' => $isDeal,
            'deal_id' => $dealId,
            'deal_created' => $dealCreated,
        ];
    }
}
