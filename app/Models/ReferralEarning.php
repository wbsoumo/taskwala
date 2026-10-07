<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReferralEarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'referrer_id',
        'referred_user_id',
        'referral_rule_id',
        'rule_version',
        'conversion_id',
        'reward_type',
        'reward_amount',
        'status',
        'reference',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'reward_amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($earning) {
            if (empty($earning->public_id)) {
                $earning->public_id = (string) Str::uuid();
            }
        });
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function rule()
    {
        return $this->belongsTo(ReferralRule::class, 'referral_rule_id');
    }

    public function conversion()
    {
        return $this->belongsTo(Conversion::class);
    }
}
