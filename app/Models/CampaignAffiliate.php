<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignAffiliate extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'user_id',
        'affiliate_payout',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'affiliate_payout' => 'decimal:2',
        ];
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
