<?php

namespace App\Services\Deals;

use App\Models\Deal;
use App\Models\DealSyncLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class DealAggregatorService
{
    protected TelegramDealService $telegramService;
    protected EarnKaroService $earnkaroService;

    public function __construct(
        TelegramDealService $telegramService,
        EarnKaroService $earnkaroService
    ) {
        $this->telegramService = $telegramService;
        $this->earnkaroService = $earnkaroService;
    }

    /**
     * Run full aggregation from configured Telegram channels.
     */
    public function syncAll(?string $channelFilter = null, int $limit = 50): array
    {
        $summary = [
            'total_found' => 0,
            'total_added' => 0,
            'total_skipped' => 0,
            'total_failed' => 0,
            'channels' => [],
        ];

        try {
            $parsedDeals = $this->telegramService->fetchMessages($channelFilter, $limit);
            $summary['total_found'] = count($parsedDeals);

            foreach ($parsedDeals as $item) {
                $result = $this->processAndStoreDeal($item);
                if ($result['status'] === 'added') {
                    $summary['total_added']++;
                } elseif ($result['status'] === 'skipped') {
                    $summary['total_skipped']++;
                } else {
                    $summary['total_failed']++;
                }
            }

            $overallStatus = ($summary['total_failed'] > 0 && $summary['total_added'] === 0 && $summary['total_found'] > 0) 
                ? 'failed' 
                : ($summary['total_failed'] > 0 ? 'partial' : 'success');

            DealSyncLog::create([
                'channel' => $channelFilter ?: 'All Channels',
                'status' => $overallStatus,
                'deals_found' => $summary['total_found'],
                'deals_added' => $summary['total_added'],
                'deals_failed' => $summary['total_failed'],
                'error_message' => $summary['total_failed'] > 0 ? "Failed processing {$summary['total_failed']} deals" : null,
                'raw_response' => [
                    'skipped' => $summary['total_skipped'],
                    'filter' => $channelFilter,
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Deal sync aggregation error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            DealSyncLog::create([
                'channel' => $channelFilter ?: 'All Channels',
                'status' => 'failed',
                'deals_found' => 0,
                'deals_added' => 0,
                'deals_failed' => 1,
                'error_message' => $e->getMessage(),
            ]);

            $summary['error'] = $e->getMessage();
        }

        return $summary;
    }

    /**
     * Process an individual deal payload, check duplicates, convert via EarnKaro, and save.
     */
    public function processAndStoreDeal(array $payload): array
    {
        $originalUrl = trim($payload['original_url'] ?? '');
        if (empty($originalUrl)) {
            return ['status' => 'failed', 'reason' => 'Empty original URL'];
        }

        // 1. Duplicate check via normalized URL hash
        $urlHash = Deal::generateUrlHash($originalUrl);
        $existing = Deal::where('url_hash', $urlHash)->first();
        if ($existing) {
            // If already exists, optionally refresh price if updated
            if (!empty($payload['price']) && $payload['price'] != $existing->price) {
                $existing->update([
                    'price' => $payload['price'],
                    'discount_percent' => $payload['discount_percent'] ?? $existing->discount_percent,
                    'posted_at' => now(),
                ]);
            }
            return ['status' => 'skipped', 'reason' => 'Duplicate deal already exists', 'deal_id' => $existing->id];
        }

        // 2. Convert URL with EarnKaro API
        $conversion = $this->earnkaroService->convertUrl($originalUrl);

        // 3. Save Deal
        try {
            $deal = Deal::create([
                'title' => $payload['title'] ?? 'Exclusive Deal',
                'description' => $payload['description'] ?? null,
                'original_url' => $originalUrl,
                'affiliate_url' => $conversion['affiliate_url'] ?? null,
                'url_hash' => $urlHash,
                'source_channel' => $payload['source_channel'] ?? 'telegram',
                'source_message_id' => $payload['source_message_id'] ?? null,
                'image_url' => $payload['image_url'] ?? null,
                'price' => $payload['price'] ?? null,
                'original_price' => $payload['original_price'] ?? null,
                'discount_percent' => $payload['discount_percent'] ?? null,
                'commission_rate' => $payload['commission_rate'] ?? null,
                'category' => $payload['category'] ?? 'General',
                'status' => 'active',
                'conversion_status' => $conversion['status'] ?? 'pending',
                'conversion_error' => $conversion['error'] ?? null,
                'posted_at' => $payload['posted_at'] ?? now(),
            ]);

            return ['status' => 'added', 'deal' => $deal];
        } catch (\Throwable $e) {
            Log::error('Error storing deal: ' . $e->getMessage());
            return ['status' => 'failed', 'reason' => $e->getMessage()];
        }
    }

    /**
     * Re-attempt EarnKaro link conversion for a specific deal.
     */
    public function retryDealConversion(Deal $deal): array
    {
        $conversion = $this->earnkaroService->convertUrl($deal->original_url);

        $deal->update([
            'affiliate_url' => $conversion['affiliate_url'] ?? $deal->affiliate_url,
            'conversion_status' => $conversion['status'],
            'conversion_error' => $conversion['error'],
        ]);

        return $conversion;
    }
}
