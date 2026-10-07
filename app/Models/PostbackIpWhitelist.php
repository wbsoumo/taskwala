<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostbackIpWhitelist extends Model
{
    use HasFactory;

    protected $fillable = [
        'postback_provider_id',
        'ip_address',
        'description',
    ];

    public function provider()
    {
        return $this->belongsTo(PostbackProvider::class, 'postback_provider_id');
    }
}
