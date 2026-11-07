<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignCollateral extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'title',
        'media_type',
        'category_preset',
        'file_path',
        'thumbnail_path',
        'default_caption',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'media_url',
        'thumbnail_url',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function getMediaUrlAttribute(): string
    {
        if (empty($this->file_path)) {
            return '';
        }

        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return preg_replace('#^(https?://[^/]+)/public/storage/#', '$1/storage/', $this->file_path);
        }

        $clean = ltrim(preg_replace('#^/?(public/)?(storage/)?#', '', $this->file_path), '/');
        $baseUrl = rtrim(config('app.url', 'https://taskwala.co.in'), '/');
        $baseUrl = preg_replace('/^http:/', 'https:', $baseUrl);
        return $baseUrl . '/storage/' . $clean;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (empty($this->thumbnail_path)) {
            return null;
        }

        if (str_starts_with($this->thumbnail_path, 'http://') || str_starts_with($this->thumbnail_path, 'https://')) {
            return preg_replace('#^(https?://[^/]+)/public/storage/#', '$1/storage/', $this->thumbnail_path);
        }

        $clean = ltrim(preg_replace('#^/?(public/)?(storage/)?#', '', $this->thumbnail_path), '/');
        $baseUrl = rtrim(config('app.url', 'https://taskwala.co.in'), '/');
        $baseUrl = preg_replace('/^http:/', 'https:', $baseUrl);
        return $baseUrl . '/storage/' . $clean;
    }
}
