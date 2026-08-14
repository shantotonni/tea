<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlendQuestion extends Model
{
    protected $fillable = ['key', 'label', 'is_published', 'sort_order'];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function options()
    {
        return $this->hasMany(BlendOption::class, 'question_id')->orderBy('sort_order')->orderBy('id');
    }
}
