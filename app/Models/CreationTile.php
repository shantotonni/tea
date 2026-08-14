<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreationTile extends Model
{
    protected $fillable = ['image', 'label', 'meta', 'target', 'is_wide', 'is_published', 'sort_order'];

    protected $casts = [
        'is_wide' => 'boolean',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];
}
