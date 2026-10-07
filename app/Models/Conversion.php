<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Conversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'click_id',
        'campaign_id',
        'user_id',
        'link_id',
        'postback_provider_id',
        'provider_conversion_id',
        'status',
        'conversion_time',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'conversion_time' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($conversion) {
            if (empty($conversion->public_id)) {
                $conversion->public_id = (string) Str::uuid();
            }
        });
    }

    public function click()
    {
        return $this->belongsTo(Click::class, 'click_id', 'click_id');
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function link()
    {
        return $this->belongsTo(AffiliateLink::class, 'link_id');
    }

    public function provider()
    {
        return $this->belongsTo(PostbackProvider::class, 'postback_provider_id');
    }

    public function payoutSnapshot()
    {
        return $this->hasOne(PayoutSnapshot::class);
    }

    public function customerPayout()
    {
        return $this->hasOne(CustomerPayout::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(ConversionStatusHistory::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
