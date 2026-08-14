<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    // supported icon names (svg path lives in the storefront component)
    const NAMES = ['Facebook', 'Instagram', 'YouTube', 'TikTok', 'WhatsApp', 'X', 'LinkedIn'];

    protected $fillable = ['name', 'href', 'is_published', 'sort_order'];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];
}
