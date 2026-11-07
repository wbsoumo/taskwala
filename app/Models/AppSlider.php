<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSlider extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'badge_text',
        'media_type',
        'media_url',
        'thumbnail_url',
        'action_type',
        'action_value',
        'auto_play_video',
        'loop_video',
        'order',
        'is_active',
    ];

    protected $casts = [
        'auto_play_video' => 'boolean',
        'loop_video' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    protected $appends = [
        'media_full_url',
        'thumbnail_full_url',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('order', 'asc')->orderBy('id', 'desc');
    }

    public function getMediaFullUrlAttribute(): string
    {
        $url = $this->media_url ?? '';
        if (empty($url)) {
            return '';
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        $clean = ltrim(preg_replace('#^(public/)?(storage/)?#', '', $url), '/');
        return asset('storage/' . $clean);
    }

    public function getThumbnailFullUrlAttribute(): ?string
    {
        $url = $this->thumbnail_url ?? '';
        if (empty($url)) {
            return null;
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        $clean = ltrim(preg_replace('#^(public/)?(storage/)?#', '', $url), '/');
        return asset('storage/' . $clean);
    }

    public function getTargetCampaignAttribute(): ?Campaign
    {
        if ($this->action_type === 'campaign' && !empty($this->action_value)) {
            return Campaign::find($this->action_value);
        }
        return null;
    }

    public function getTargetDealAttribute(): ?Deal
    {
        if ($this->action_type === 'deal' && !empty($this->action_value)) {
            return Deal::where('id', $this->action_value)
                ->orWhere('public_id', $this->action_value)
                ->first();
        }
        return null;
    }
}
