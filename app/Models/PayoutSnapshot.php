<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayoutSnapshot extends Model
{
    use HasFactory;

    public $timestamps = false; // Custom created_at timestamp

    protected $fillable = [
        'conversion_id',
        'advertiser_payout',
        'affiliate_allocated_payout',
        'customer_payout',
        'affiliate_commission',
        'platform_margin',
        'currency',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'advertiser_payout' => 'decimal:2',
            'affiliate_allocated_payout' => 'decimal:2',
            'customer_payout' => 'decimal:2',
            'affiliate_commission' => 'decimal:2',
            'platform_margin' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function conversion()
    {
        return $this->belongsTo(Conversion::class);
    }
}
