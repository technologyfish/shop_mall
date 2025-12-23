<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'user_id', 'product_id', 'quantity', 'selected'
    ];

    protected $casts = [
        'selected' => 'boolean',
    ];

    /**
     * 所属用户
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 商品信息
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}







