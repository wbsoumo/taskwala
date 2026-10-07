<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AffiliateLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'secure_token',
        'campaign_id',
        'user_id',
        'allocated_affiliate_payout',
        'customer_payout',
        'affiliate_commission',
        'status',
        'click_count',
        'conversion_count',
    ];

    protected function casts(): array
    {
        return [
            'allocated_affiliate_payout' => 'decimal:2',
            'customer_payout' => 'decimal:2',
            'affiliate_commission' => 'decimal:2',
            'click_count' => 'integer',
            'conversion_count' => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($link) {
            if (empty($link->public_id)) {
                $link->public_id = (string) Str::uuid();
            }
            if (empty($link->secure_token)) {
                // Generate cryptographically secure 40-char token
                $link->secure_token = Str::random(40);
            }
        });
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clicks()
    {
        return $this->hasMany(Click::class, 'link_id');
    }

    public function conversions()
    {
        return $this->hasMany(Conversion::class, 'link_id');
    }

    public function getPublicUrlAttribute(): string
    {
        return url('/go/' . $this->secure_token);
    }
}
