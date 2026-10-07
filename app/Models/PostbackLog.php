<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostbackLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'postback_provider_id',
        'endpoint',
        'source_ip',
        'http_method',
        'headers',
        'payload',
        'response_payload',
        'auth_result',
        'ip_whitelist_result',
        'click_validation_result',
        'conversion_result',
        'rejection_reason',
        'response_code',
        'processing_time_ms',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'payload' => 'array',
            'response_payload' => 'array',
            'auth_result' => 'boolean',
            'ip_whitelist_result' => 'boolean',
            'click_validation_result' => 'boolean',
            'conversion_result' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function provider()
    {
        return $this->belongsTo(PostbackProvider::class, 'postback_provider_id');
    }
}
