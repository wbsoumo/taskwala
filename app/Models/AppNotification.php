<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'deeplink',
        'image_url',
        'target_type',
        'total_targeted',
        'sent_count',
        'failed_count',
        'created_by_admin_id',
    ];

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'app_notification_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function getReadCountAttribute(): int
    {
        return $this->userNotifications()->where('is_read', true)->count();
    }

    public function getUnreadCountAttribute(): int
    {
        return $this->userNotifications()->where('is_read', false)->count();
    }
}
