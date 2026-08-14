<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    protected $fillable = [
        'category', 'title', 'title_bn', 'excerpt', 'image',
        'author', 'role', 'read_time', 'is_featured', 'is_published',
        'sort_order', 'published_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
        'published_at' => 'date',
    ];

    public const CATEGORIES = [
        'brewing' => 'Brewing Guide',
        'health' => 'Health & Wellness',
        'garden' => 'Sreemangal Notes',
    ];

    public function getCatLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Journal';
    }
}
