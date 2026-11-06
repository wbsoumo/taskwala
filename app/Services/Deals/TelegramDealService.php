<?php

namespace App\Services\Deals;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramDealService
{
    protected ?string $botToken;
    protected array $channels;
    protected bool $enabled;
    protected string $apiBase;

    public function __construct()
    {
        $this->botToken = Setting::get('deals_telegram_bot_token');
        $rawChannels = Setting::get('deals_telegram_channels', '@dealsmagnet, @lootdeals');
        $this->channels = array_filter(array_map('trim', explode(',', $rawChannels)));
        $this->enabled = (bool) Setting::get('deals_telegram_enabled', '0');
        $this->apiBase = rtrim(Setting::get('deals_telegram_api_base', 'https://api.telegram.org'), '/');
    }

    /**
     * Test scraping a public Telegram channel directly without needing a Bot token.
     */
    public function testPublicChannel(string $channel): array
    {
        $channel = trim($channel);
        if (empty($channel)) {
            return [
                'success' => false,
                'message' => 'Please provide a Telegram channel username (e.g. @dealsmagnet, @lootdeals).',
            ];
        }

        $cleanChannel = ltrim($channel, '@');
        if (str_contains($cleanChannel, 't.me/')) {
            $parts = explode('t.me/', $cleanChannel);
            $cleanChannel = trim(explode('/', $parts[1] ?? '')[0]);
        }

        try {
            $deals = $this->fetchFromPublicChannelWebPreview($cleanChannel, 10);

            if (!empty($deals)) {
                $first = $deals[0];
                $title = \Illuminate\Support\Str::limit($first['title'] ?? 'Deal', 55);
                $priceText = !empty($first['price']) ? '₹' . number_format($first['price'], 2) : 'Offer';
                return [
                    'success' => true,
                    'channel' => '@' . $cleanChannel,
                    'count' => count($deals),
                    'message' => "Successfully connected! Scraped " . count($deals) . " deals from @{$cleanChannel}. Latest: \"{$title}\" ({$priceText}).",
                    'sample' => [
                        'title' => $first['title'],
                        'price' => $first['price'],
                        'original_price' => $first['original_price'],
                        'discount_percent' => $first['discount_percent'],
                        'category' => $first['category'],
                        'original_url' => $first['original_url'],
                    ],
                ];
            }

            // If no deals parsed, check if page is reachable
            $url = "https://t.me/s/{$cleanChannel}";
            $res = Http::timeout(6)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml',
            ])->get($url);

            if ($res->successful()) {
                return [
                    'success' => true,
                    'channel' => '@' . $cleanChannel,
                    'count' => 0,
                    'message' => "Channel @{$cleanChannel} is reachable! However, no active e-commerce deals were found in recent posts.",
                ];
            }

            return [
                'success' => false,
                'message' => "Channel https://t.me/s/{$cleanChannel} returned HTTP {$res->status()}. Please ensure the channel is public.",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error testing public channel: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test connection to Telegram Bot API.
     */
    public function testBotToken(?string $token = null, ?string $apiBase = null): array
    {
        $token = trim($token ?? $this->botToken ?? '');
        $apiBase = rtrim($apiBase ?? $this->apiBase ?? 'https://api.telegram.org', '/');

        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'Telegram Bot Token is not provided.',
            ];
        }

        try {
            $response = Http::timeout(4)->get("{$apiBase}/bot{$token}/getMe");

            if ($response->successful() && ($response->json('ok') === true)) {
                $user = $response->json('result');
                $name = $user['first_name'] ?? 'Bot';
                $username = isset($user['username']) ? '@' . $user['username'] : '';
                return [
                    'success' => true,
                    'message' => "Telegram Bot connected successfully: {$name} ({$username})",
                    'bot' => $user,
                ];
            }

            $desc = $response->json('description') ?? ('HTTP ' . $response->status());
            if ($response->status() === 401 || str_contains(strtolower($desc), 'unauthorized')) {
                return [
                    'success' => false,
                    'message' => 'Telegram Bot Token is invalid or unauthorized. Please make sure the token is copied completely from @BotFather.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Telegram API Response: ' . $desc,
            ];
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'cURL error 28') || str_contains($msg, 'timed out')) {
                return [
                    'success' => false,
                    'is_firewall' => true,
                    'message' => 'Hosting server firewall blocked outbound connection to ' . $apiBase . ' (cURL error 28 timeout). Note: Public channels are fetched automatically via web preview without needing the Bot API, or you can specify a Telegram reverse proxy URL.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Telegram connection failed: ' . $msg,
            ];
        }
    }

    /**
     * Fetch raw deal messages from Telegram channels.
     */
    public function fetchMessages(?string $channelFilter = null, int $limit = 50): array
    {
        $allParsedDeals = [];
        $channelsToScan = $channelFilter ? [$channelFilter] : $this->channels;

        // 1. Fetch via Telegram Bot API getUpdates if bot token is present
        if (!empty($this->botToken)) {
            $botDeals = $this->fetchFromBotUpdates($limit);
            foreach ($botDeals as $deal) {
                $allParsedDeals[] = $deal;
            }
        }

        // 2. For each configured public channel, also check public web preview for fresh posts
        foreach ($channelsToScan as $channel) {
            $publicDeals = $this->fetchFromPublicChannelWebPreview($channel, $limit);
            foreach ($publicDeals as $deal) {
                $allParsedDeals[] = $deal;
            }
        }

        return $allParsedDeals;
    }

    /**
     * Fetch updates via Telegram Bot API getUpdates.
     */
    protected function fetchFromBotUpdates(int $limit = 50): array
    {
        $deals = [];
        $lastUpdateId = (int) Setting::get('deals_telegram_last_update_id', 0);
        $offset = $lastUpdateId ? $lastUpdateId + 1 : 0;

        try {
            $url = "{$this->apiBase}/bot{$this->botToken}/getUpdates";
            $response = Http::timeout(10)->get($url, [
                'offset' => $offset,
                'limit' => min($limit, 100),
                'allowed_updates' => json_encode(['channel_post', 'message']),
            ]);

            if ($response->successful() && $response->json('ok')) {
                $updates = $response->json('result', []);
                $maxId = $lastUpdateId;

                foreach ($updates as $update) {
                    $updateId = $update['update_id'] ?? 0;
                    if ($updateId > $maxId) {
                        $maxId = $updateId;
                    }

                    $post = $update['channel_post'] ?? $update['message'] ?? null;
                    if (!$post) {
                        continue;
                    }

                    $parsed = $this->parseBotPost($post);
                    if ($parsed) {
                        $deals[] = $parsed;
                    }
                }

                if ($maxId > $lastUpdateId) {
                    Setting::set('deals_telegram_last_update_id', (string) $maxId, 'deals');
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Telegram getUpdates exception: ' . $e->getMessage());
        }

        return $deals;
    }

    /**
     * Parse a Telegram Bot API message/channel_post object.
     */
    protected function parseBotPost(array $post): ?array
    {
        $text = $post['text'] ?? $post['caption'] ?? '';
        $entities = $post['entities'] ?? $post['caption_entities'] ?? [];
        $messageId = (string) ($post['message_id'] ?? '');
        $chat = $post['chat'] ?? [];
        $channelName = $chat['username'] ? '@' . $chat['username'] : ($chat['title'] ?? 'Telegram');
        $date = isset($post['date']) ? Carbon::createFromTimestamp($post['date']) : now();

        // Extract photo URL if present
        $imageUrl = null;
        if (!empty($post['photo']) && is_array($post['photo'])) {
            $largest = end($post['photo']);
            $fileId = $largest['file_id'] ?? null;
            if ($fileId) {
                $imageUrl = $this->resolveTelegramFileUrl($fileId);
            }
        }

        // Extract product URL
        $extractedUrl = $this->extractUrlFromTextAndEntities($text, $entities);
        if (!$extractedUrl) {
            return null; // Not an e-commerce / deal message
        }

        return $this->buildDealPayload($text, $extractedUrl, $channelName, $messageId, $imageUrl, $date);
    }

    /**
     * Resolve Telegram File ID to direct public file URL.
     */
    protected function resolveTelegramFileUrl(string $fileId): ?string
    {
        if (empty($this->botToken)) return null;

        try {
            $res = Http::timeout(5)->get("{$this->apiBase}/bot{$this->botToken}/getFile", [
                'file_id' => $fileId,
            ]);

            if ($res->successful() && $res->json('ok')) {
                $filePath = $res->json('result.file_path');
                if ($filePath) {
                    return "{$this->apiBase}/file/bot{$this->botToken}/{$filePath}";
                }
            }
        } catch (\Throwable $e) {
            // Silently proceed
        }

        return null;
    }

    /**
     * Fetch posts from public Telegram channel web preview (https://t.me/s/{channel}).
     */
    public function fetchFromPublicChannelWebPreview(string $channel, int $limit = 25): array
    {
        $deals = [];
        $cleanChannel = ltrim(trim($channel), '@');
        // If channel is a full URL like https://t.me/channel_name, parse basename
        if (str_contains($cleanChannel, 't.me/')) {
            $parts = explode('t.me/', $cleanChannel);
            $cleanChannel = trim(explode('/', $parts[1] ?? '')[0]);
        }

        if (empty($cleanChannel)) {
            return [];
        }

        try {
            $url = "https://t.me/s/{$cleanChannel}";
            $response = Http::timeout(10)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml',
            ])->get($url);

            if ($response->successful()) {
                $html = $response->body();
                $deals = $this->parsePublicChannelHtml($html, '@' . $cleanChannel);
            }
        } catch (\Throwable $e) {
            Log::warning("Failed to fetch public telegram web preview for {$cleanChannel}: " . $e->getMessage());
        }

        return array_slice($deals, 0, $limit);
    }

    /**
     * Parse HTML messages from t.me/s/{channel}.
     */
    protected function parsePublicChannelHtml(string $html, string $channelName): array
    {
        $deals = [];

        // Match individual message cards
        preg_match_all('/<div class="tgme_widget_message[ "][^>]*data-post="([^"]+)"[^>]*>(.*?)<\/div>\s*<\/div>\s*<\/div>/is', $html, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            // Fallback simpler regex
            preg_match_all('/data-post="([^"]+)"(.*?)tgme_widget_message_date/is', $html, $matches, PREG_SET_ORDER);
        }

        foreach ($matches as $match) {
            $postIdentifier = $match[1] ?? ''; // e.g. "lootdeals/1234"
            $msgContent = $match[2] ?? '';

            // Extract message ID
            $msgId = '';
            if (str_contains($postIdentifier, '/')) {
                $parts = explode('/', $postIdentifier);
                $msgId = end($parts);
            }

            // Extract text
            $text = '';
            if (preg_match('/<div class="tgme_widget_message_text[^"]*"[^>]*>(.*?)<\/div>/is', $msgContent, $textMatch)) {
                $rawHtmlText = $textMatch[1];
                // Convert <br> to newline
                $cleanText = preg_replace('/<br\s*\/?>/i', "\n", $rawHtmlText);
                $text = trim(strip_tags($cleanText));
            }

            // Extract photo if any
            $imageUrl = null;
            if (preg_match('/background-image:url\(\'([^\']+)\'\)/i', $msgContent, $imgMatch)) {
                $imageUrl = $imgMatch[1];
            } elseif (preg_match('/<img[^>]+src="([^">]+)"/i', $msgContent, $imgMatch)) {
                $imageUrl = $imgMatch[1];
            }

            // Extract links from <a> tags and raw text
            $extractedUrl = null;
            preg_match_all('/href="([^"]+)"/i', $msgContent, $linkMatches);
            if (!empty($linkMatches[1])) {
                foreach ($linkMatches[1] as $href) {
                    if ($this->isValidDealUrl($href)) {
                        $extractedUrl = $href;
                        break;
                    }
                }
            }

            if (!$extractedUrl) {
                $extractedUrl = $this->extractUrlFromTextAndEntities($text, []);
            }

            if ($extractedUrl && !empty($text)) {
                $parsed = $this->buildDealPayload($text, $extractedUrl, $channelName, $msgId, $imageUrl, now());
                if ($parsed) {
                    $deals[] = $parsed;
                }
            }
        }

        return $deals;
    }

    /**
     * Extract product / deal URL from message text and entities.
     */
    protected function extractUrlFromTextAndEntities(string $text, array $entities): ?string
    {
        // 1. Check entities (text_link or url)
        foreach ($entities as $entity) {
            $type = $entity['type'] ?? '';
            if ($type === 'text_link' && !empty($entity['url'])) {
                if ($this->isValidDealUrl($entity['url'])) {
                    return $entity['url'];
                }
            } elseif ($type === 'url') {
                $offset = $entity['offset'] ?? 0;
                $length = $entity['length'] ?? 0;
                $urlStr = mb_substr($text, $offset, $length);
                if ($this->isValidDealUrl($urlStr)) {
                    return $urlStr;
                }
            }
        }

        // 2. Regex search in raw text
        preg_match_all('/https?:\/\/[^\s<>"]+/i', $text, $urlMatches);
        if (!empty($urlMatches[0])) {
            foreach ($urlMatches[0] as $candidate) {
                // Remove trailing punctuation
                $candidate = rtrim($candidate, '.,;:!?)]');
                if ($this->isValidDealUrl($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * Filter out Telegram internal links and validate deal URL.
     */
    public function isValidDealUrl(string $url): bool
    {
        $url = trim($url);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        $host = preg_replace('/^www\./', '', $host);

        // Discard Telegram internal links
        if (str_contains($host, 't.me') || str_contains($host, 'telegram.me') || str_contains($host, 'telegram.org')) {
            return false;
        }

        // Must be http or https
        $scheme = parse_url($url, PHP_URL_SCHEME);
        return in_array($scheme, ['http', 'https']);
    }

    /**
     * Build unified deal array from parsed fields.
     */
    public function buildDealPayload(
        string $text,
        string $url,
        string $channelName,
        string $messageId = '',
        ?string $imageUrl = null,
        ?Carbon $date = null
    ): array {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text))));
        $titleCandidate = reset($lines) ?: 'Special Online Deal';
        $cleanedCandidate = trim(preg_replace('/^[🔥⚡💥🎉🎁🚨!\s]+|[🔥⚡💥🎉🎁🚨!\s]+$/u', '', $titleCandidate));

        // If the first line is just a short hype/alert banner (e.g. "CRAZY LOOT!"), inspect the second line
        $hypeKeywords = ['loot', 'alert', 'deal', 'deals', 'offer', 'offers', 'wow', 'crazy', 'huge', 'fast', 'hurry', 'trick'];
        $isPureHype = false;
        if (count($lines) > 1 && mb_strlen($cleanedCandidate) < 25) {
            foreach ($hypeKeywords as $hw) {
                if (stripos($cleanedCandidate, $hw) !== false) {
                    $isPureHype = true;
                    break;
                }
            }
        }

        if ($isPureHype && isset($lines[1])) {
            $secondCleaned = trim(preg_replace('/^[🔥⚡💥🎉🎁🚨!\s]+|[🔥⚡💥🎉🎁🚨!\s]+$/u', '', $lines[1]));
            if (mb_strlen($secondCleaned) >= 4) {
                $cleanedCandidate = $secondCleaned;
            }
        }

        $title = mb_substr(trim($cleanedCandidate), 0, 250);
        if (empty($title)) {
            $title = 'Hot Trending Deal';
        }

        // Extract Price, Original Price, Discount %
        $price = $this->extractPrice($text);
        $originalPrice = $this->extractOriginalPrice($text);
        $discount = $this->extractDiscount($text);

        // Fallback calculation for discount
        if (!$discount && $price && $originalPrice && $originalPrice > $price) {
            $discount = (int) round((($originalPrice - $price) / $originalPrice) * 100);
        }

        // Auto Category
        $category = $this->detectCategory($text . ' ' . $title);

        // Commission guess based on domain/category
        $commissionRate = $this->estimateCommissionRate($url, $category);

        return [
            'title' => $title,
            'description' => trim($text),
            'original_url' => $url,
            'source_channel' => $channelName,
            'source_message_id' => $messageId,
            'image_url' => $imageUrl,
            'price' => $price,
            'original_price' => $originalPrice,
            'discount_percent' => $discount,
            'commission_rate' => $commissionRate,
            'category' => $category,
            'posted_at' => $date ?: now(),
        ];
    }

    /**
     * Extract sale price from text.
     */
    public function extractPrice(string $text): ?float
    {
        // Matches "Deal Price: ₹899", "Price: 999", "at ₹1,499", "Rs. 499", "Pay 299"
        $patterns = [
            '/(?:deal\s*price|price|now\s*at|at|pay|only|offer\s*price)[\s:]*(?:₹|rs\.?|inr)?\s*([0-9]{1,3}(?:,[0-9]{3})*(?:\.[0-9]{1,2})?|[0-9]+(?:\.[0-9]{1,2})?)/i',
            '/(?:₹|rs\.?)\s*([0-9]{1,3}(?:,[0-9]{3})*(?:\.[0-9]{1,2})?|[0-9]+(?:\.[0-9]{1,2})?)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $raw = str_replace(',', '', $matches[1]);
                $val = (float) $raw;
                if ($val > 0 && $val < 10000000) {
                    return $val;
                }
            }
        }

        return null;
    }

    /**
     * Extract original price / MRP from text.
     */
    public function extractOriginalPrice(string $text): ?float
    {
        // Matches "MRP: ₹3,990", "Was ₹1999", "Original: Rs 4999"
        $pattern = '/(?:mrp|was|original\s*price|worth)[\s:]*(?:₹|rs\.?|inr)?\s*([0-9]{1,3}(?:,[0-9]{3})*(?:\.[0-9]{1,2})?|[0-9]+(?:\.[0-9]{1,2})?)/i';
        if (preg_match($pattern, $text, $matches)) {
            $raw = str_replace(',', '', $matches[1]);
            $val = (float) $raw;
            if ($val > 0 && $val < 10000000) {
                return $val;
            }
        }

        return null;
    }

    /**
     * Extract discount percentage from text.
     */
    public function extractDiscount(string $text): ?int
    {
        // Matches "80% off", "Flat 50% discount", "(78% OFF)"
        if (preg_match('/([0-9]{1,2})%\s*(?:off|discount)/i', $text, $matches)) {
            $val = (int) $matches[1];
            if ($val > 0 && $val <= 99) {
                return $val;
            }
        }

        return null;
    }

    /**
     * Categorize deal based on keywords.
     */
    public function detectCategory(string $text): string
    {
        $text = strtolower($text);

        $categories = [
            'Electronics' => ['phone', 'mobile', 'smartphone', 'laptop', 'headphone', 'earphone', 'neckband', 'earbuds', 'watch', 'smartwatch', 'charger', 'cable', 'powerbank', 'tv', 'speaker', 'soundbar', 'bluetooth', 'mouse', 'keyboard', 'tablet', 'gadget'],
            'Fashion' => ['shirt', 't-shirt', 'tshirt', 'jeans', 'shoes', 'sneakers', 'saree', 'kurti', 'dress', 'jacket', 'hoodie', 'watch', 'wallet', 'trousers', 'sunglasses', 'footwear', 'apparel'],
            'Beauty & Personal Care' => ['shampoo', 'cream', 'serum', 'lotion', 'perfume', 'deodorant', 'makeup', 'lipstick', 'sunscreen', 'trimmer', 'shaver', 'facewash', 'soap', 'skincare'],
            'Home & Kitchen' => ['bedsheet', 'cooker', 'pan', 'bottle', 'flask', 'pillow', 'knife', 'curtain', 'vacuum', 'mop', 'mixer', 'grinder', 'kitchen', 'furniture', 'light', 'led'],
            'Grocery & Food' => ['almond', 'cashew', 'tea', 'coffee', 'chocolate', 'oil', 'ghee', 'rice', 'biscuit', 'maggi', 'snack', 'grocery'],
            'Recharge & Finance' => ['recharge', 'cashback', 'bill', 'electricity', 'credit card', 'coupon', 'dth'],
        ];

        foreach ($categories as $cat => $keywords) {
            foreach ($keywords as $kw) {
                if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $text)) {
                    return $cat;
                }
            }
        }

        return 'General';
    }

    /**
     * Estimate publisher commission rate based on merchant domain.
     */
    protected function estimateCommissionRate(string $url, string $category): string
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

        if (str_contains($host, 'amazon') || str_contains($host, 'amzn')) {
            return 'Up to 9% Profit';
        }
        if (str_contains($host, 'flipkart') || str_contains($host, 'fkp')) {
            return 'Up to 12% Profit';
        }
        if (str_contains($host, 'myntra')) {
            return 'Flat 8.5% Profit';
        }
        if (str_contains($host, 'ajio')) {
            return 'Flat 10% Profit';
        }
        if (str_contains($host, 'tatacliq')) {
            return 'Up to 7% Profit';
        }
        if (str_contains($host, 'boat') || str_contains($host, 'noise')) {
            return 'Flat 15% Profit';
        }

        return match ($category) {
            'Electronics' => 'Up to 6% Profit',
            'Fashion' => 'Up to 10% Profit',
            'Beauty & Personal Care' => 'Up to 12% Profit',
            'Home & Kitchen' => 'Up to 8% Profit',
            default => 'Up to 10% Profit',
        };
    }
}
