<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'email', 'phone', 'password', 'avatar', 'status', 'stripe_customer_id', 'nickname', 'is_subscriber', 'first_order_discount', 'off_code'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_subscriber' => 'boolean',
        'status' => 'integer',
        'first_order_discount' => 'integer',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * 用户的订阅
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * 用户的订单
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * 用户的购物车
     */
    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * 用户的收货地址
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
}




