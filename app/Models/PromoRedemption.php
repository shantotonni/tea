<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoRedemption extends Model
{
    protected $fillable = ['promo_code_id', 'customer_id', 'order_id', 'email', 'discount'];

    protected $casts = [
        'discount' => 'integer',
    ];

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }
}
