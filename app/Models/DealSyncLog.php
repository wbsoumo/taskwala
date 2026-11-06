<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel',
        'status',
        'deals_found',
        'deals_added',
        'deals_failed',
        'error_message',
        'raw_response',
    ];

    protected $casts = [
        'deals_found' => 'integer',
        'deals_added' => 'integer',
        'deals_failed' => 'integer',
        'raw_response' => 'array',
    ];
}
