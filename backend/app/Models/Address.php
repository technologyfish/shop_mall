<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id', 'name', 'first_name', 'last_name', 'email', 'phone', 'address', 'city', 'postcode', 'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * 所属用户
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 完整地址
     */
    public function getFullAddressAttribute()
    {
        return $this->address;
    }
}


