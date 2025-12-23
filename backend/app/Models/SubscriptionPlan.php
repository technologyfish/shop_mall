<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    // 状态常量
    const STATUS_ACTIVE = 1;   // 启用
    const STATUS_INACTIVE = 0; // 禁用
    
    protected $fillable = [
        'name',
        'description',
        'image',
        'monthly_delivery_date',
        'stripe_price_id',
        'plan_type',
        'bottles_per_delivery',
        'price',
        'delivery_day',
        'status',
        'sort'
    ];

    protected $casts = [
        'status' => 'integer',
        'sort' => 'integer',
        'bottles_per_delivery' => 'integer',
        'delivery_day' => 'integer',
        'price' => 'decimal:2',
        'monthly_delivery_date' => 'date',
    ];

    /**
     * 获取此计划的所有订阅
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
}

