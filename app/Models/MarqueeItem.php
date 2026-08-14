<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarqueeItem extends Model
{
    protected $fillable = ['label', 'is_published', 'sort_order'];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];
}
