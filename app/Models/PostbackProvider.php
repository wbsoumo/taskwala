<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PostbackProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'auth_method',
        'secret_key',
        'status',
    ];

    protected $hidden = [
        'secret_key',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($provider) {
            if (empty($provider->slug)) {
                $provider->slug = Str::slug($provider->name);
            }
        });
    }

    public function ipWhitelists()
    {
        return $this->hasMany(PostbackIpWhitelist::class);
    }

    public function conversions()
    {
        return $this->hasMany(Conversion::class);
    }

    public function logs()
    {
        return $this->hasMany(PostbackLog::class);
    }
}
