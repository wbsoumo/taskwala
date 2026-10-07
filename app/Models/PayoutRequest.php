<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PayoutRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'user_id',
        'amount',
        'upi_id',
        'upi_holder_name',
        'status',
        'transaction_id',
        'admin_notes',
        'processed_at',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            if (empty($request->public_id)) {
                $request->public_id = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function processedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'processed_by');
    }
}
