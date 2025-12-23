<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'content', 'image', 'images', 
        'price', 'original_price', 'stock', 'sales', 
        'is_featured', 'is_new', 'tags', 'sort', 'status',
        'shipping_fee', 'free_shipping_threshold'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'images' => 'array',
        'tags' => 'array',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'status' => 'boolean',
        'sort' => 'integer',
        'stock' => 'integer',
        'sales' => 'integer'
    ];

    /**
     * 库存是否充足
     */
    public function hasStock($quantity = 1)
    {
        return $this->stock >= $quantity;
    }

    /**
     * 减少库存
     */
    public function decreaseStock($quantity)
    {
        return $this->decrement('stock', $quantity);
    }

    /**
     * 增加销量
     */
    public function increaseSales($quantity)
    {
        return $this->increment('sales', $quantity);
    }
}


