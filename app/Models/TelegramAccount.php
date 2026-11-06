<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'api_id',
        'api_hash',
        'session_string',
        'telegram_user_id',
        'first_name',
        'last_name',
        'username',
        'is_connected',
        'status',
        'phone_code_hash',
        'last_error',
        'last_connected_at',
    ];

    protected $casts = [
        'is_connected' => 'boolean',
        'last_connected_at' => 'datetime',
        'telegram_user_id' => 'integer',
    ];

    protected $hidden = [
        'session_string',
        'api_hash',
    ];

    /**
     * Channels associated with this account.
     */
    public function channels(): HasMany
    {
        return $this->hasMany(TelegramMonitoredChannel::class, 'telegram_account_id');
    }

    /**
     * Monitored channels.
     */
    public function monitoredChannels(): HasMany
    {
        return $this->channels()->where('is_monitored', true);
    }

    /**
     * Get user full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')) ?: ($this->username ? '@' . $this->username : 'Telegram User');
    }

    /**
     * Get display username.
     */
    public function getDisplayUsernameAttribute(): string
    {
        return $this->username ? '@' . ltrim($this->username, '@') : 'No username';
    }
}
