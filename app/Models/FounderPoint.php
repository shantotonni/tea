<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FounderPoint extends Model
{
    protected $fillable = ['num', 'title', 'text', 'is_published', 'sort_order'];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];
}
