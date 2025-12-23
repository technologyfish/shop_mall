<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDiscount extends Model
{
    protected $table = 'user_discounts';

    protected $fillable = [
        'user_id',
        'promotion_id',
        'discount_value',
        'is_used',
        'used_at',
        'order_id',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    /**
     * 关联到用户
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 关联到促销活动
     */
    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    /**
     * 关联到订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * 标记折扣已使用
     */
    public function markAsUsed($orderId)
    {
        $this->is_used = true;
        $this->used_at = now();
        $this->order_id = $orderId;
        $this->save();
    }

    /**
     * 获取用户可用的折扣
     */
    public static function getAvailableDiscountForUser($userId)
    {
        return self::where('user_id', $userId)
            ->where('is_used', false)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}



