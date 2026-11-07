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
    protected ProductMetadataScraperService $scraperService;

    public function __construct(
        TelegramDealService $telegramService,
        EarnKaroService $earnkaroService,
        ProductMetadataScraperService $scraperService
    ) {
        $this->telegramService = $telegramService;
        $this->earnkaroService = $earnkaroService;
        $this->scraperService = $scraperService;
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
     * Process an individual deal payload, check duplicates, scrape metadata (image, title, MRP, price), convert via EarnKaro, and save.
     */
     public function processAndStoreDeal(array $payload): array
     {
         $originalUrl = trim($payload['original_url'] ?? '');
         if (empty($originalUrl)) {
             return ['status' => 'failed', 'reason' => 'Empty original URL'];
         }

         // 1. Scrape product metadata (image URL, title, price, MRP) from the e-commerce store
         $meta = $this->scraperService->scrape($originalUrl);
         $canonicalUrl = !empty($meta['canonical_url']) ? $meta['canonical_url'] : $originalUrl;

         // 2. Duplicate check via normalized URL hash
         $urlHash = Deal::generateUrlHash($canonicalUrl);
         $existing = Deal::where('url_hash', $urlHash)->first();
         if ($existing) {
             // Refresh image, MRP, or price if updated
             $updates = [];
             if (empty($existing->image_url) && !empty($meta['image_url'])) {
                 $updates['image_url'] = $meta['image_url'];
             }
             if (!empty($meta['price']) && $meta['price'] != $existing->price) {
                 $updates['price'] = $meta['price'];
                 $updates['posted_at'] = now();
             }
             if (empty($existing->original_price) && !empty($meta['original_price'])) {
                 $updates['original_price'] = $meta['original_price'];
             }
             if (!empty($meta['discount_percent'])) {
                 $updates['discount_percent'] = $meta['discount_percent'];
             }
             if (!empty($updates)) {
                 $existing->update($updates);
             }

             return ['status' => 'skipped', 'reason' => 'Duplicate deal already exists', 'deal_id' => $existing->id];
         }

         // 3. Resolve Title, Image, Price, MRP
         $rawTitleCandidate = !empty($meta['title']) ? $meta['title'] : ($payload['title'] ?? null);
         $finalTitle = \App\Models\Deal::cleanTitleString($rawTitleCandidate, null, $payload['description'] ?? null);
         $finalImageUrl = !empty($meta['image_url']) ? $meta['image_url'] : ($payload['image_url'] ?? null);
         $finalPrice = !empty($meta['price']) ? $meta['price'] : ($payload['price'] ?? null);
         $finalOriginalPrice = !empty($meta['original_price']) ? $meta['original_price'] : ($payload['original_price'] ?? null);
         $finalDiscount = !empty($meta['discount_percent']) ? $meta['discount_percent'] : ($payload['discount_percent'] ?? null);

         // Clean and sanitize text description (strip raw URLs so URLs are not printed directly in text)
         $rawDesc = $payload['description'] ?? null;
         $cleanDesc = $rawDesc ? ProductMetadataScraperService::stripRawUrlsFromText($rawDesc) : null;

         // 4. Convert URL with EarnKaro API
         $conversion = $this->earnkaroService->convertUrl($canonicalUrl);

         // 5. Save Deal into database
         try {
             $deal = Deal::create([
                 'title' => $finalTitle,
                 'description' => $cleanDesc,
                 'original_url' => $canonicalUrl,
                 'affiliate_url' => $conversion['affiliate_url'] ?? null,
                 'url_hash' => $urlHash,
                 'source_channel' => $payload['source_channel'] ?? 'telegram',
                 'source_message_id' => $payload['source_message_id'] ?? null,
                 'image_url' => $finalImageUrl,
                 'price' => $finalPrice,
                 'original_price' => $finalOriginalPrice, // MRP stored in original_price
                 'discount_percent' => $finalDiscount,
                 'commission_rate' => $payload['commission_rate'] ?? null,
                 'category' => !empty($meta['category']) ? $meta['category'] : ($payload['category'] ?? 'General'),
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
     * Re-scrape and enrich an existing deal with image URL, title, MRP, and current price.
     */
    public function enrichDealMetadata(Deal $deal, bool $force = false): array
    {
        $meta = $this->scraperService->scrape($deal->original_url);

        if (!$meta['success']) {
            return [
                'success' => false,
                'error' => $meta['error'] ?? 'Could not extract product metadata',
            ];
        }

        $updates = [];
        if (!empty($meta['image_url']) && ($force || empty($deal->image_url))) {
            $updates['image_url'] = $meta['image_url'];
        }
        if (!empty($meta['title']) && ($force || empty($deal->title) || str_contains(strtolower($deal->title), 'deal') || str_contains($deal->title, 'http') || mb_strlen($deal->title) < 30)) {
            $updates['title'] = $meta['title'];
        } elseif (!empty($deal->title) && str_contains($deal->title, 'http')) {
            $updates['title'] = trim(preg_replace('/https?:\/\/[^\s]+/i', '', $deal->title));
        }

        if (!empty($deal->description) && str_contains($deal->description, 'http')) {
            $updates['description'] = ProductMetadataScraperService::stripRawUrlsFromText($deal->description);
        }

        if (!empty($meta['price']) && ($force || empty($deal->price))) {
            $updates['price'] = $meta['price'];
        }
        if (!empty($meta['original_price']) && ($force || empty($deal->original_price))) {
            $updates['original_price'] = $meta['original_price'];
        }
        if (!empty($meta['discount_percent'])) {
            $updates['discount_percent'] = $meta['discount_percent'];
        }

        if (!empty($updates)) {
            $deal->update($updates);
        }

        return [
            'success' => true,
            'updates' => $updates,
            'deal' => $deal->fresh(),
        ];
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
