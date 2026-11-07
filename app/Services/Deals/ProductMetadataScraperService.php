<?php

namespace App\Services\Deals;

use Illuminate\Support\Facades\Log;

class ProductMetadataScraperService
{
    /**
     * Crawl and extract product metadata (image URL, title, price, MRP, discount)
     * from any e-commerce link (Amazon, Flipkart, Myntra, Ajio, TataCliq, etc.).
     */
    public function scrape(string $url): array
    {
        $url = trim($url);
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'error' => 'Invalid URL provided',
            ];
        }

        $result = [
            'success' => false,
            'canonical_url' => $url,
            'title' => null,
            'image_url' => null,
            'price' => null,
            'original_price' => null, // MRP
            'discount_percent' => null,
            'category' => null,
            'platform' => $this->detectPlatform($url),
        ];

        try {
            // Step 1: Follow redirects & fetch HTML using Social Media Crawler UA (best for OpenGraph & Schema)
            $fetchRes = $this->fetchHtml($url, 'crawler');
            $html = $fetchRes['html'];
            $result['canonical_url'] = $fetchRes['effective_url'] ?: $url;
            $result['platform'] = $this->detectPlatform($result['canonical_url']);

            // If crawler UA received 403 or empty response, fallback to standard Browser UA
            if (empty($html) || $fetchRes['http_code'] === 403 || $fetchRes['http_code'] === 401) {
                $browserFetch = $this->fetchHtml($result['canonical_url'], 'browser');
                if (!empty($browserFetch['html'])) {
                    $html = $browserFetch['html'];
                    $result['canonical_url'] = $browserFetch['effective_url'] ?: $result['canonical_url'];
                    $result['platform'] = $this->detectPlatform($result['canonical_url']);
                }
            }

            if (empty($html)) {
                return $result;
            }

            // Step 2: Extract Schema.org JSON-LD (high accuracy for Amazon, Myntra, TataCliq, etc.)
            $this->extractFromJsonLd($html, $result);

            // Step 3: Extract OpenGraph and Twitter Cards metadata
            $this->extractFromOpenGraph($html, $result);

            // Step 4: Platform-Specific Price, MRP & High-Res Image enhancements
            $this->applyPlatformSpecificParsers($html, $result);

            // Step 5: Clean and sanitize Title and URLs
            if (!empty($result['title'])) {
                $result['title'] = $this->cleanProductTitle($result['title'], $result['platform']);
            }

            // Step 6: Calculate Discount % if MRP and Selling Price exist
            if (!empty($result['original_price']) && !empty($result['price']) && $result['original_price'] > $result['price']) {
                $computed = (int) round((($result['original_price'] - $result['price']) / $result['original_price']) * 100);
                if ($computed > 0 && $computed < 100) {
                    $result['discount_percent'] = $computed;
                }
            }

            // Check if we extracted at least an image or title or price
            if (!empty($result['image_url']) || !empty($result['title']) || !empty($result['price'])) {
                $result['success'] = true;
            }

        } catch (\Throwable $e) {
            Log::warning("ProductMetadataScraperService failed for {$url}: " . $e->getMessage());
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Fetch HTML with automatic redirect following and gzip decompression.
     */
    protected function fetchHtml(string $url, string $mode = 'crawler'): array
    {
        $ua = ($mode === 'browser')
            ? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
            : 'WhatsApp/2.21.12.21 A';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 14,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_ENCODING => '', // Handles gzip, deflate automatically
            CURLOPT_USERAGENT => $ua,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9,hi;q=0.8',
                'Cache-Control: no-cache',
            ],
        ]);

        $html = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        // Note: in PHP 8.0+ curl handles auto-close on handle unset, but curl_close is safe

        return [
            'html' => is_string($html) ? $html : '',
            'http_code' => $httpCode,
            'effective_url' => $effectiveUrl,
        ];
    }

    /**
     * Extract product data from schema.org JSON-LD script tags.
     */
    protected function extractFromJsonLd(string $html, array &$result): void
    {
        if (!preg_match_all('/<script[^>]+type=[\'\"]application\/ld\+json[\'\"][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            return;
        }

        foreach ($matches[1] as $jsonStr) {
            $data = json_decode(trim($jsonStr), true);
            if (!is_array($data)) {
                continue;
            }

            // Normalise to list of objects
            $items = isset($data['@type']) ? [$data] : (isset($data[0]) ? $data : []);

            foreach ($items as $item) {
                if (!is_array($item)) continue;

                $type = $item['@type'] ?? '';
                if (is_array($type)) {
                    $type = in_array('Product', $type) ? 'Product' : '';
                }

                if ($type === 'Product') {
                    // Title / Name
                    if (empty($result['title']) && !empty($item['name'])) {
                        $result['title'] = html_entity_decode((string) $item['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }

                    // Image
                    if (empty($result['image_url']) && !empty($item['image'])) {
                        $img = is_array($item['image']) ? reset($item['image']) : $item['image'];
                        if (is_string($img) && filter_var($img, FILTER_VALIDATE_URL)) {
                            $result['image_url'] = $img;
                        }
                    }

                    // Offers / Price
                    if (!empty($item['offers'])) {
                        $offers = isset($item['offers'][0]) && is_array($item['offers'][0]) ? $item['offers'][0] : $item['offers'];
                        if (is_array($offers)) {
                            if (empty($result['price']) && isset($offers['price']) && is_numeric($offers['price'])) {
                                $result['price'] = (float) $offers['price'];
                            } elseif (empty($result['price']) && isset($offers['lowPrice']) && is_numeric($offers['lowPrice'])) {
                                $result['price'] = (float) $offers['lowPrice'];
                            }

                            if (empty($result['original_price']) && isset($offers['highPrice']) && is_numeric($offers['highPrice'])) {
                                $result['original_price'] = (float) $offers['highPrice'];
                            }
                        }
                    }

                    // Category
                    if (empty($result['category']) && !empty($item['category'])) {
                        $result['category'] = is_string($item['category']) ? $item['category'] : null;
                    }
                }
            }
        }
    }

    /**
     * Extract OpenGraph & Twitter metadata.
     */
    protected function extractFromOpenGraph(string $html, array &$result): void
    {
        // 1. og:title
        if (empty($result['title'])) {
            if (preg_match('/<meta[^>]+(?:property|name)=[\'\"](?:og:title|twitter:title)[\'\"][^>]+content=[\'\"]([^\'\"]+)[\'\"]/i', $html, $m) ||
                preg_match('/<meta[^>]+content=[\'\"]([^\'\"]+)[\'\"][^>]+(?:property|name)=[\'\"](?:og:title|twitter:title)[\'\"]/i', $html, $m)) {
                $result['title'] = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            } elseif (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
                $result['title'] = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        // 2. og:image
        if (empty($result['image_url'])) {
            if (preg_match('/<meta[^>]+(?:property|name)=[\'\"](?:og:image|twitter:image|twitter:image:src)[\'\"][^>]+content=[\'\"]([^\'\"]+)[\'\"]/i', $html, $m) ||
                preg_match('/<meta[^>]+content=[\'\"]([^\'\"]+)[\'\"][^>]+(?:property|name)=[\'\"](?:og:image|twitter:image|twitter:image:src)[\'\"]/i', $html, $m)) {
                $img = trim($m[1]);
                if (filter_var($img, FILTER_VALIDATE_URL)) {
                    $result['image_url'] = $img;
                }
            }
        }

        // 3. og:price:amount or product:price:amount
        if (empty($result['price'])) {
            if (preg_match('/<meta[^>]+(?:property|name)=[\'\"](?:og:price:amount|product:price:amount)[\'\"][^>]+content=[\'\"]([0-9\.]+)[\'\"]/i', $html, $m) ||
                preg_match('/<meta[^>]+content=[\'\"]([0-9\.]+)[\'\"][^>]+(?:property|name)=[\'\"](?:og:price:amount|product:price:amount)[\'\"]/i', $html, $m)) {
                $result['price'] = (float) $m[1];
            }
        }
    }

    /**
     * Platform-specific scrapers and image enhancers.
     */
    protected function applyPlatformSpecificParsers(string $html, array &$result): void
    {
        $platform = $result['platform'];

        // --- AMAZON ---
        if ($platform === 'amazon' || str_contains($result['canonical_url'], 'amazon.')) {
            // Clean up Amazon Image: If it has sizing tokens or social preview borders, get base image
            if (!empty($result['image_url'])) {
                if (preg_match('/(https:\/\/[^\/]+\/images\/I\/[a-zA-Z0-9_\-\+%\.]+?)(?:\._[^\/]+?_\.jpg|\.jpg_[^\s"\'<>]+|\._[^\/]+?\.(?:jpg|jpeg|png|webp))/i', $result['image_url'], $cleanImg)) {
                    $result['image_url'] = $cleanImg[1] . '.jpg';
                }
            }

            // Fallback for Amazon Search / Category Listing pages
            if (empty($result['image_url']) && preg_match('/(?:class=[\'"][^\'"]*s-image[^\'"]*[\'"][^>]+src=[\'"]|src=[\'"])(https:\/\/m\.media-amazon\.com\/images\/I\/[a-zA-Z0-9_\-\+%\.]+)/i', $html, $sImg)) {
                $base = preg_replace('/(?:\._[^\/]+?_\.jpg|\.jpg_[^\s"\'<>]+|\._[^\/]+?\.(?:jpg|jpeg|png|webp))$/i', '', $sImg[1]);
                $result['image_url'] = $base . '.jpg';
            }

            // Extract Amazon MRP
            if (empty($result['original_price'])) {
                // Matches "M.R.P.: ₹5,800.00" or "MRP: ₹5,800"
                if (preg_match('/(?:M\.R\.P\.|MRP)[^₹0-9]*([₹0-9,]+(?:\.[0-9]{2})?)/u', $html, $mrpM)) {
                    $clean = (float) str_replace([',', '₹', ' '], '', $mrpM[1]);
                    if ($clean > 0 && $clean < 10000000) {
                        $result['original_price'] = $clean;
                    }
                }
            }

            // Extract Amazon Selling Price if missing
            if (empty($result['price'])) {
                if (preg_match('/class=\"a-price-whole\"[^>]*>([0-9,]+)/i', $html, $pMatch)) {
                    $val = (float) str_replace(',', '', $pMatch[1]);
                    if ($val > 0) $result['price'] = $val;
                }
            }
        }

        // --- FLIPKART ---
        if ($platform === 'flipkart' || str_contains($result['canonical_url'], 'flipkart.')) {
            // Upgrade Flipkart Image to high-resolution (832x832)
            if (!empty($result['image_url']) && str_contains($result['image_url'], 'rukminim2.flixcart.com/image/')) {
                $result['image_url'] = preg_replace('/\/image\/[0-9]+\/[0-9]+\//', '/image/832/832/', $result['image_url']);
            } elseif (empty($result['image_url']) && preg_match('/https:\/\/rukminim[0-9]*\.flixcart\.com\/image\/[0-9]+\/[0-9]+\/([a-zA-Z0-9_\-\/]+?\.(?:jpeg|jpg|png|webp))/i', $html, $fkMatches)) {
                $result['image_url'] = 'https://rukminim2.flixcart.com/image/832/832/' . $fkMatches[1];
            }

            // Extract Flipkart MRP from state JSON: "label_2":{"value":{"text":"₹747"}},"label_1":{"value":{"text":"MRP
            if (empty($result['original_price'])) {
                if (preg_match('/\"text\":\"(?:₹|&#8377;|Rs\.?)?([0-9,]+)\"\}\},\"[^\"]+\":\{\"value\":\{\"text\":\"MRP/u', $html, $mrpM) ||
                    preg_match('/\"mrp\":\s*\{\"value\":\s*([0-9]+)/', $html, $mrpM)) {
                    $result['original_price'] = (float) str_replace(',', '', $mrpM[1]);
                }
            }

            // Extract Flipkart Selling Price from actionType CART_ADD or state JSON
            if (empty($result['price'])) {
                if (preg_match('/\"actionType\":\"CART_ADD\",\"price\":([0-9]+)/', $html, $pM) ||
                    preg_match('/\"specialPrice\":\s*\{\"value\":\s*([0-9]+)/', $html, $pM) ||
                    preg_match('/\"finalPrice\":\s*\{\"value\":\s*([0-9]+)/', $html, $pM) ||
                    preg_match('/\"price\":([0-9]+(?:\.[0-9]{1,2})?)/', $html, $pM)) {
                    $result['price'] = (float) $pM[1];
                }
            }
        }

        // --- MYNTRA ---
        if ($platform === 'myntra' || str_contains($result['canonical_url'], 'myntra.')) {
            // Myntra prices are usually in JSON-LD, but also check pdp-mrp / pdp-price
            if (empty($result['original_price']) && preg_match('/class=\"pdp-mrp\"[^>]*>(?:₹|Rs\.?)?\s*<s>([0-9,]+)<\/s>/i', $html, $mrpM)) {
                $result['original_price'] = (float) str_replace(',', '', $mrpM[1]);
            }
            if (empty($result['price']) && preg_match('/class=\"pdp-price\"[^>]*>(?:₹|Rs\.?)?\s*<strong>([0-9,]+)<\/strong>/i', $html, $pM)) {
                $result['price'] = (float) str_replace(',', '', $pM[1]);
            }
        }

        // --- AJIO ---
        if ($platform === 'ajio' || str_contains($result['canonical_url'], 'ajio.') || str_contains($result['canonical_url'], 'ajiio.co')) {
            // Extract Ajio product images from assets
            if (empty($result['image_url']) && preg_match_all('/https:\/\/assets\.ajio\.com\/medias\/sys_master\/root\/[a-zA-Z0-9_\-\/]+?\.(?:jpg|jpeg|png|webp)/i', $html, $ajioMatches)) {
                $firstAjio = $ajioMatches[0][0];
                $result['image_url'] = preg_replace('/-[0-9]+Wx[0-9]+H-/i', '-473Wx593H-', $firstAjio);
            }

            // If Ajio returned 403 or title is missing, attempt parsing slug from URL
            if (empty($result['title']) && preg_match('/ajio\.com\/([^\/]+)\/p\//i', $result['canonical_url'], $slugM)) {
                $slug = str_replace('-', ' ', $slugM[1]);
                $result['title'] = ucwords(trim($slug));
            }
            // Check Ajio price tags in HTML if fetched
            if (empty($result['price']) && preg_match('/class=\"prod-sp\"[^>]*>(?:₹|Rs\.?)?\s*([0-9,]+)/i', $html, $pM)) {
                $result['price'] = (float) str_replace(',', '', $pM[1]);
            }
            if (empty($result['original_price']) && preg_match('/class=\"prod-cp\"[^>]*>(?:₹|Rs\.?)?\s*([0-9,]+)/i', $html, $mrpM)) {
                $result['original_price'] = (float) str_replace(',', '', $mrpM[1]);
            }
        }

        // --- GENERIC FALLBACK FOR ANY STORE ---
        if (empty($result['image_url']) && preg_match_all('/<img[^>]+(?:data-src|data-lazy-src|src)=[\'"]([^\'"]+\.(?:jpg|jpeg|png|webp)[^\'"]*)[\'"]/i', $html, $anyImgs)) {
            foreach ($anyImgs[1] as $candidate) {
                $candidate = html_entity_decode(trim($candidate));
                if (preg_match('/(?:icon|logo|badge|pixel|spacer|loader|sprite|placeholder|1x1)/i', $candidate)) continue;
                if (filter_var($candidate, FILTER_VALIDATE_URL)) {
                    $result['image_url'] = $candidate;
                    break;
                }
            }
        }
    }

    /**
     * Clean boilerplate e-commerce suffixes and strip any raw URLs from the title.
     */
    public function cleanProductTitle(string $title, string $platform = 'general'): string
    {
        // 1. Strip raw URLs if present
        $title = preg_replace('/https?:\/\/[^\s]+/i', '', $title);

        // 2. Strip store suffixes
        $patterns = [
            '/\s*:\s*Amazon\.in:\s*.*$/i',
            '/\s*:\s*Amazon\.in$/i',
            '/\s*-\s*Price in India,\s*Buy\s+.*$/i',
            '/\s*\|\s*Flipkart\.com$/i',
            '/\s*-\s*Flipkart$/i',
            '/\s*\|\s*Myntra$/i',
            '/\s*Buy\s+.*?\s+online at Myntra$/i',
            '/\s*\|\s*Ajio$/i',
            '/\s*\|\s*Tata CLiQ$/i',
            '/\s*\|\s*Nykaa$/i',
        ];

        foreach ($patterns as $pattern) {
            $title = preg_replace($pattern, '', $title);
        }

        // 3. Check for error page titles
        $lower = strtolower(trim($title));
        $errorPhrases = [
            'page not found', 'robot check', 'access denied', '404', 
            'something went wrong', 'amazon.in: error', '503 - service unavailable', 
            'service unavailable', 'error 503'
        ];
        foreach ($errorPhrases as $ep) {
            if (str_contains($lower, $ep)) {
                return '';
            }
        }

        // 4. Strip leading hype emojis or excessive punctuation
        $title = preg_replace('/^[\s🔥⚡💥🎉🎁🚨!|:-]+|[\s🔥⚡💥🎉🎁🚨!|:-]+$/u', '', $title);

        return mb_substr(trim($title), 0, 300);
    }

    /**
     * Remove or sanitize raw URLs from text descriptions.
     */
    public static function stripRawUrlsFromText(string $text): string
    {
        // Strip URLs like https://amzn.to/xyz or http://...
        $cleaned = preg_replace('/https?:\/\/[^\s<>"]+/i', '', $text);
        // Clean up redundant blank lines
        $lines = array_map('trim', explode("\n", $cleaned));
        $filtered = array_filter($lines, fn($line) => !empty($line));
        return implode("\n", $filtered);
    }

    /**
     * Detect e-commerce merchant platform from URL.
     */
    public function detectPlatform(string $url): string
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

        if (str_contains($host, 'amazon') || str_contains($host, 'amzn.')) {
            return 'amazon';
        }
        if (str_contains($host, 'flipkart') || str_contains($host, 'fkrt.')) {
            return 'flipkart';
        }
        if (str_contains($host, 'myntra')) {
            return 'myntra';
        }
        if (str_contains($host, 'ajio')) {
            return 'ajio';
        }
        if (str_contains($host, 'tatacliq')) {
            return 'tatacliq';
        }
        if (str_contains($host, 'nykaa')) {
            return 'nykaa';
        }
        if (str_contains($host, 'meesho')) {
            return 'meesho';
        }

        return 'general';
    }
}
