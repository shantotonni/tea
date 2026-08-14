<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Review extends Model
{
    protected $fillable = [
        'name', 'city', 'text', 'lang', 'product',
        'rating', 'verified', 'is_published', 'sort_order',
    ];

    protected $casts = [
        'rating' => 'integer',
        'verified' => 'boolean',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** first character of the name — Bengali or Latin, for the avatar bubble */
    public function getAvatarAttribute(): string
    {
        return Str::upper(Str::substr(trim($this->name), 0, 1));
    }
}
