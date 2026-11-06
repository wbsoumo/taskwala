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
        });
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
