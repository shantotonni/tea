<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'customer_id', 'customer_name', 'customer_email',
        'phone', 'address', 'city', 'payment_method',
        'items_count', 'subtotal', 'shipping', 'promo_code', 'discount', 'total', 'note', 'status', 'channel',
    ];

    protected $casts = [
        'items_count' => 'integer',
        'subtotal' => 'integer',
        'shipping' => 'integer',
        'discount' => 'integer',
        'total' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
