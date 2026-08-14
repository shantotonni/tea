<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerGroup extends Model
{
    protected $fillable = ['name', 'description'];

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_group_members')->withTimestamps();
    }
}
