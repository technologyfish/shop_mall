<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'product_image',
        'price', 'quantity', 'total_amount'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public $timestamps = false;

    /**
     * 所属订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * 商品
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}







