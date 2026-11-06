<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'message_id',
        'sender_id',
        'text',
        'media_type',
        'media_url',
        'media_local_path',
        'extracted_urls',
        'is_deal',
        'deal_id',
        'is_edited',
        'raw_payload',
        'posted_at',
        'edited_at',
    ];

    protected $casts = [
        'channel_id' => 'integer',
        'message_id' => 'integer',
        'sender_id' => 'integer',
        'extracted_urls' => 'array',
        'raw_payload' => 'array',
        'is_deal' => 'boolean',
        'is_edited' => 'boolean',
        'posted_at' => 'datetime',
        'edited_at' => 'datetime',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(TelegramMonitoredChannel::class, 'channel_id', 'channel_id');
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }

    public function scopeDealsOnly(Builder $query): Builder
    {
        return $query->where('is_deal', true);
    }
}
