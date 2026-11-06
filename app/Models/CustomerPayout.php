<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversion_id',
        'link_id',
        'click_id',
        'customer_name',
        'upi_id',
        'upi_holder_name',
        'mobile_number',
        'payout_amount',
        'status',
        'transaction_id',
        'admin_notes',
        'processed_at',
        'processed_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'payout_amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function conversion()
    {
        return $this->belongsTo(Conversion::class);
    }

    public function link()
    {
        return $this->belongsTo(AffiliateLink::class, 'link_id');
    }

    public function processedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'processed_by_admin_id');
    }
}
