<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReferralRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'name',
        'version',
        'reward_type',
        'reward_value',
        'campaign_id',
        'category',
        'condition_type',
        'is_recurring',
        'priority',
        'status',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'reward_value' => 'decimal:2',
            'is_recurring' => 'boolean',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rule) {
            if (empty($rule->public_id)) {
                $rule->public_id = (string) Str::uuid();
            }
        });
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
