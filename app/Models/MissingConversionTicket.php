<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MissingConversionTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'user_id',
        'campaign_id',
        'click_id',
        'order_reference_id',
        'task_date',
        'screenshot_path',
        'notes',
        'status',
        'admin_notes',
        'approved_payout',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'task_date' => 'date',
        'resolved_at' => 'datetime',
        'approved_payout' => 'decimal:2',
    ];

    protected $appends = [
        'screenshot_url',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->public_id)) {
                $ticket->public_id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function resolvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'resolved_by');
    }

    public function getScreenshotUrlAttribute(): ?string
    {
        if (empty($this->screenshot_path)) {
            return null;
        }

        if (str_starts_with($this->screenshot_path, 'http://') || str_starts_with($this->screenshot_path, 'https://')) {
            return $this->screenshot_path;
        }

        return url($this->screenshot_path);
    }
}
