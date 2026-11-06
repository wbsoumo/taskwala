<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramMonitoredChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_account_id',
        'channel_id',
        'access_hash',
        'title',
        'username',
        'type',
        'participants_count',
        'is_monitored',
        'sync_status',
        'historical_limit',
        'historical_synced_count',
        'total_messages_imported',
        'total_deals_imported',
        'last_scraped_at',
        'last_message_id',
    ];

    protected $casts = [
        'channel_id' => 'integer',
        'is_monitored' => 'boolean',
        'participants_count' => 'integer',
        'historical_limit' => 'integer',
        'historical_synced_count' => 'integer',
        'total_messages_imported' => 'integer',
        'total_deals_imported' => 'integer',
        'last_message_id' => 'integer',
        'last_scraped_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(TelegramAccount::class, 'telegram_account_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TelegramMessage::class, 'channel_id', 'channel_id');
    }

    public function scopeMonitored(Builder $query): Builder
    {
        return $query->where('is_monitored', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('username', 'like', "%{$term}%")
              ->orWhere('channel_id', 'like', "%{$term}%");
        });
    }

    public function getTelegramLinkAttribute(): ?string
    {
        if (!empty($this->username)) {
            return 'https://t.me/' . ltrim($this->username, '@');
        }

        return null;
    }

    public function getFormattedTypeAttribute(): string
    {
        return match (strtolower($this->type)) {
            'channel' => 'Public Channel',
            'supergroup' => 'Supergroup',
            'group' => 'Group Chat',
            default => ucfirst($this->type ?? 'Channel'),
        };
    }
}
