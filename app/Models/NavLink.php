<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavLink extends Model
{
    protected $fillable = ['label', 'target', 'is_cta', 'is_published', 'sort_order'];

    protected $casts = [
        'is_cta' => 'boolean',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];
}
