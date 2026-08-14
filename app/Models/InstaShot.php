<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstaShot extends Model
{
    protected $fillable = ['image', 'caption', 'likes', 'is_published', 'sort_order'];

    protected $casts = [
        'likes' => 'integer',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];
}
