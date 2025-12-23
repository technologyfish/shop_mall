<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionDelivery extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'subscription_id',
        'user_id',
        'order_id',
        'delivery_date',
        'status',
        'shipped_at',
        'tracking_number',
        'tracking_company',
        'notes'
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'shipped_at' => 'datetime',
    ];

    /**
     * 关联订阅
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 关联订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}




