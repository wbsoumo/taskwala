<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'name',
        'slug',
        'description',
        'short_description',
        'logo_url',
        'theme',
        'advertiser_name',
        'category',
        'campaign_type',
        'landing_url',
        'conversion_event',
        'advertiser_payout',
        'default_affiliate_payout',
        'currency',
        'status',
        'start_date',
        'end_date',
        'terms',
        'kpi_requirements',
        'duplicate_conversion_rules',
        'postback_provider_id',
        'postback_secret_key',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'advertiser_payout' => 'decimal:2',
            'default_affiliate_payout' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($campaign) {
            if (empty($campaign->public_id)) {
                $campaign->public_id = (string) Str::uuid();
            }
            if (empty($campaign->slug)) {
                $campaign->slug = Str::slug($campaign->name);
            }
            if (empty($campaign->theme)) {
                $campaign->theme = 'gradient_blue';
            }
            if (empty($campaign->postback_secret_key)) {
                $campaign->postback_secret_key = Str::random(24);
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    public function postbackProvider()
    {
        return $this->belongsTo(PostbackProvider::class, 'postback_provider_id');
    }

    public function affiliateAllocations()
    {
        return $this->hasMany(CampaignAffiliate::class);
    }

    public function links()
    {
        return $this->hasMany(AffiliateLink::class);
    }

    public function clicks()
    {
        return $this->hasMany(Click::class);
    }

    public function conversions()
    {
        return $this->hasMany(Conversion::class);
    }

    /**
     * Get allocated affiliate payout for a specific user.
     */
    public function getAffiliatePayoutForUser(int $userId): float
    {
        $allocation = $this->affiliateAllocations()->where('user_id', $userId)->where('status', 'allowed')->first();
        if ($allocation) {
            return (float) $allocation->affiliate_payout;
        }

        return (float) $this->default_affiliate_payout;
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();

        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }
}
