<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Customer extends Authenticatable implements JWTSubject
{
    protected $fillable = [
        'name', 'email', 'password', 'phone', 'city', 'tier', 'orders_count', 'spent',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'orders_count' => 'integer',
        'spent' => 'integer',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function groups()
    {
        return $this->belongsToMany(CustomerGroup::class, 'customer_group_members')->withTimestamps();
    }

    // ---- JWTSubject ----
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return ['typ' => 'customer'];
    }
}
