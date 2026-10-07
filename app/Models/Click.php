<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Click extends Model
{
    use HasFactory;

    public $timestamps = false; // We use created_at custom timestamp

    protected $fillable = [
        'click_id',
        'link_id',
        'campaign_id',
        'user_id',
        'allocated_affiliate_payout',
        'customer_payout',
        'affiliate_commission',
        'ip_address',
        'user_agent',
        'referrer',
        'device_type',
        'os',
        'browser',
        'status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'allocated_affiliate_payout' => 'decimal:2',
            'customer_payout' => 'decimal:2',
            'affiliate_commission' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function link()
    {
        return $this->belongsTo(AffiliateLink::class, 'link_id');
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conversion()
    {
        return $this->hasOne(Conversion::class, 'click_id', 'click_id');
    }

    public function customerPayout()
    {
        return $this->hasOne(CustomerPayout::class, 'click_id', 'click_id');
    }
}
