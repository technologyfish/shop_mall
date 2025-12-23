<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_no', 'user_id', 'total_amount', 'discount_amount', 'shipping_fee', 'pay_amount', 'status',
        'pay_status', 'payment_status', 'payment_method', 'payment_no',
        'shipping_name', 'shipping_email', 'shipping_phone', 'shipping_address', 'shipping_city', 'shipping_postal_code', 
        'shipping_company', 'shipping_no', 'remark', 'promotion_id',
        'paid_at', 'shipping_time', 'completed_at',
        'is_subscription', 'subscription_id'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'pay_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'shipping_time' => 'datetime',
        'completed_at' => 'datetime',
        'status' => 'integer',
        'pay_status' => 'integer'
    ];

    // 订单状态常量
    const STATUS_PENDING = 0;           // 待支付
    const STATUS_WAITING_SHIPMENT = 1;  // 待发货（已支付）
    const STATUS_COMPLETED = 3;         // 已完成（已填写物流）
    const STATUS_CANCELLED = 4;         // 已取消
    
    // 兼容旧代码的别名
    const STATUS_PAID = 1;     // 已支付 (alias for WAITING_SHIPMENT)
    const STATUS_SHIPPED = 2;  // 已发货 (deprecated, use COMPLETED instead)

    /**
     * 所属用户
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 订单商品
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * 支付记录
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * 关联的订阅
     */
    public function subscription()
    {
        return $this->belongsTo(\App\Models\Subscription::class, 'subscription_id');
    }

    /**
     * 生成订单号
     */
    public static function generateOrderNo()
    {
        return date('YmdHis') . rand(100000, 999999);
    }
}



