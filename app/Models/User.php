<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /** JWT: identifier stored in the token subject claim */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /** JWT: custom claims embedded in the token */
    public function getJWTCustomClaims()
    {
        return [
            'name' => $this->name,
            'role' => $this->role,
        ];
    }
}
