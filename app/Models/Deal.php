<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Deal extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'title',
        'description',
        'original_url',
        'affiliate_url',
        'url_hash',
        'source_channel',
        'source_message_id',
        'image_url',
        'price',
        'original_price',
        'discount_percent',
        'commission_rate',
        'category',
        'status',
        'conversion_status',
        'conversion_error',
        'clicks_count',
        'posted_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount_percent' => 'integer',
        'clicks_count' => 'integer',
        'posted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($deal) {
            if (empty($deal->public_id)) {
                $deal->public_id = (string) Str::uuid();
            }

            if (empty($deal->url_hash) && !empty($deal->original_url)) {
                $deal->url_hash = static::generateUrlHash($deal->original_url);
            }

            if (empty($deal->posted_at)) {
                $deal->posted_at = now();
            }

            if (empty($deal->discount_percent) && $deal->original_price > 0 && $deal->price > 0 && $deal->original_price > $deal->price) {
                $deal->discount_percent = (int) round((($deal->original_price - $deal->price) / $deal->original_price) * 100);
            }

            // Clean title: remove any raw URLs, raw prices, and hype punctuation
            if (!empty($deal->title)) {
                $deal->title = static::cleanTitleString($deal->title, $deal->merchant_name, $deal->description);
            }

            // Clean description: strip raw URLs so URLs are not printed directly in text
            if (!empty($deal->description)) {
                $deal->description = trim(preg_replace('/https?:\/\/[^\s<>"]+/i', '', $deal->description));
            }
        });

        static::updating(function ($deal) {
            if ($deal->isDirty('title') && !empty($deal->title)) {
                $deal->title = static::cleanTitleString($deal->title, $deal->merchant_name, $deal->description);
            }

            if ($deal->isDirty('description') && !empty($deal->description)) {
                $deal->description = trim(preg_replace('/https?:\/\/[^\s<>"]+/i', '', $deal->description));
            }

            if ($deal->original_price > 0 && $deal->price > 0 && $deal->original_price > $deal->price) {
                $deal->discount_percent = (int) round((($deal->original_price - $deal->price) / $deal->original_price) * 100);
            }
        });
    }

    /**
     * Sanitize and extract clean title without raw URLs or standalone prices.
     */
    public static function cleanTitleString(?string $title, ?string $merchantName = null, ?string $description = null): string
    {
        if (empty($title)) {
            $title = '';
        }

        // 1. Strip raw URLs
        $title = preg_replace('/https?:\/\/[^\s<>"]+/i', '', $title);
        $title = preg_replace('/\b(?:amzn\.to|fkrt\.co|bit\.ly|t\.co|cutt\.ly)\/[^\s<>"]+/i', '', $title);

        // 2. Strip leading numbers/currency, e.g. "1145", "₹1145", "Rs. 999", "at 499"
        $title = preg_replace('/^\s*(?:₹|rs\.?|inr)?\s*[\d,]+(?:\.\d{1,2})?\s*(?:\/-)?\s*[-|@:]?\s*/iu', '', $title);

        // 3. Strip trailing price or standalone currency mentions
        $title = preg_replace('/\s*(?:₹|rs\.?|inr)\s*[\d,]+(?:\.\d{1,2})?\s*$/iu', '', $title);

        // 4. Strip emojis or punctuation at beginning and end
        $title = preg_replace('/^[\s🔥⚡💥🎉🎁🚨!|:,\.-]+|[\s🔥⚡💥🎉🎁🚨!|:,\.-]+$/u', '', $title);

        $title = trim($title);

        // If title is only digits (like just "1145" after stripping url), or empty
        if (empty($title) || preg_match('/^\d+$/', $title)) {
            if (!empty($description)) {
                $descClean = preg_replace('/https?:\/\/[^\s<>"]+/i', '', $description);
                $descClean = preg_replace('/^\s*(?:₹|rs\.?|inr)?\s*[\d,]+(?:\.\d{1,2})?\s*(?:\/-)?\s*[-|@:]?\s*/iu', '', $descClean);
                $descClean = trim(preg_replace('/^[\s🔥⚡💥🎉🎁🚨!|:,\.-]+|[\s🔥⚡💥🎉🎁🚨!|:,\.-]+$/u', '', $descClean));
                if (!empty($descClean) && !preg_match('/^\d+$/', $descClean)) {
                    return Str::limit($descClean, 100);
                }
            }
            $store = $merchantName ?: 'Exclusive';
            return "{$store} Special Deal";
        }

        return $title;
    }

    /**
     * Accessor for title to ensure links and prices never appear in the title/name area.
     */
    public function getTitleAttribute($value): string
    {
        return static::cleanTitleString($value, $this->merchant_name, $this->attributes['description'] ?? null);
    }

    /**
     * Get human-friendly merchant brand name from original URL.
     */
    public function getMerchantNameAttribute(): string
    {
        $host = strtolower(parse_url($this->original_url, PHP_URL_HOST) ?? '');
        if (str_contains($host, 'amazon') || str_contains($host, 'amzn')) return 'Amazon';
        if (str_contains($host, 'flipkart') || str_contains($host, 'fkrt')) return 'Flipkart';
        if (str_contains($host, 'myntra')) return 'Myntra';
        if (str_contains($host, 'ajio')) return 'Ajio';
        if (str_contains($host, 'tatacliq')) return 'Tata CLiQ';
        if (str_contains($host, 'nykaa')) return 'Nykaa';
        if (str_contains($host, 'meesho')) return 'Meesho';
        return ucfirst(explode('.', preg_replace('/^www\./', '', $host))[0] ?? 'Store');
    }

    /**
     * Normalize and hash URL for duplicate prevention.
     */
    public static function generateUrlHash(string $url): string
    {
        $parsed = parse_url(trim($url));
        if (!$parsed || !isset($parsed['host'])) {
            return hash('sha256', trim(strtolower($url)));
        }

        $scheme = $parsed['scheme'] ?? 'https';
        $host = strtolower($parsed['host']);
        $host = preg_replace('/^www\./', '', $host);
        $path = $parsed['path'] ?? '/';

        // Keep relevant product identification query params, strip trackers/affiliate tags
        $cleanQuery = '';
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
            $trackingParams = [
                'tag', 'affid', 'aff_id', 'utm_source', 'utm_medium', 'utm_campaign',
                'utm_term', 'utm_content', 'ref', 'ref_', 'ascsubtag', 'linkCode',
                'camp', 'creative', 'smid', 'source', 'cmpid', 'gad_source', 'gclid'
            ];
            foreach ($trackingParams as $p) {
                unset($queryParams[$p]);
            }
            ksort($queryParams);
            if (!empty($queryParams)) {
                $cleanQuery = '?' . http_build_query($queryParams);
            }
        }

        $canonical = $scheme . '://' . $host . rtrim($path, '/') . $cleanQuery;
        return hash('sha256', $canonical);
    }

    /**
     * Get target affiliate link or fallback to original.
     */
    public function getEffectiveUrlAttribute(): string
    {
        return !empty($this->affiliate_url) ? $this->affiliate_url : $this->original_url;
    }

    /**
     * Get computed discount percentage.
     */
    public function getComputedDiscountAttribute(): ?int
    {
        if ($this->discount_percent) {
            return $this->discount_percent;
        }

        if ($this->original_price > 0 && $this->price > 0 && $this->original_price > $this->price) {
            return (int) round((($this->original_price - $this->price) / $this->original_price) * 100);
        }

        return null;
    }

    /**
     * Query scopes.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCategory($query, ?string $category)
    {
        if ($category && $category !== 'all') {
            return $query->where('category', $category);
        }
        return $query;
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!empty($term)) {
            $term = '%' . trim($term) . '%';
            return $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                  ->orWhere('description', 'like', $term)
                  ->orWhere('source_channel', 'like', $term)
                  ->orWhere('category', 'like', $term);
            });
        }
        return $query;
    }
}
