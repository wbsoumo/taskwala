<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'public_id',
        'name',
        'email',
        'mobile_number',
        'password',
        'status',
        'upi_id',
        'upi_holder_name',
        'notes',
        'referral_code',
        'referred_by',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->public_id)) {
                $user->public_id = (string) Str::uuid();
            }
            if (empty($user->referral_code)) {
                $user->referral_code = 'REF' . strtoupper(Str::random(7));
            }
        });

        static::created(function ($user) {
            // Ensure every user has a wallet created automatically
            $user->wallet()->create([
                'balance' => 0.00,
                'pending_balance' => 0.00,
                'total_withdrawn' => 0.00,
                'currency' => config('platform.currency', 'INR'),
            ]);
        });
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referredUsers()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function referralEarnings()
    {
        return $this->hasMany(ReferralEarning::class, 'referrer_id');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function campaignAllocations()
    {
        return $this->hasMany(CampaignAffiliate::class);
    }

    public function links()
    {
        return $this->hasMany(AffiliateLink::class);
    }

    public function clicks()
    {
        return $this->hasMany(Click::class);
    }

    public function conversions()
    {
        return $this->hasMany(Conversion::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
