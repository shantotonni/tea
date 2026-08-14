<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlendOption extends Model
{
    protected $fillable = ['question_id', 'opt_id', 'title', 'hint', 'icon', 'sort_order'];

    protected $casts = [
        'question_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function question()
    {
        return $this->belongsTo(BlendQuestion::class, 'question_id');
    }
}
