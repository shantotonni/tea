<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionNote extends Model
{
    protected $fillable = ['icon', 'label', 'is_published', 'sort_order'];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];
}
