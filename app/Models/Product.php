<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'name', 'sku', 'category', 'blurb', 'image',
        'tag', 'weight', 'price', 'old_price', 'stock', 'reviews', 'status',
        'rating', 'is_featured', 'in_gift_box', 'details',
    ];

    protected $casts = [
        'price' => 'integer',
        'old_price' => 'integer',
        'stock' => 'integer',
        'reviews' => 'integer',
        'rating' => 'float',
        'is_featured' => 'boolean',
        'in_gift_box' => 'boolean',
        'details' => 'array',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
