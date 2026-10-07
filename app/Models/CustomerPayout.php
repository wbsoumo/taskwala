<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversion_id',
        'customer_name',
        'upi_id',
        'upi_holder_name',
        'mobile_number',
        'payout_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'payout_amount' => 'decimal:2',
        ];
    }

    public function conversion()
    {
        return $this->belongsTo(Conversion::class);
    }
}
