<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PAST_DUE = 'past_due';

    protected $fillable = [
        'user_id',
        'plan_id',
        'stripe_subscription_id',
        'stripe_customer_id',
        'plan_name',
        'plan_type',
        'bottles_per_delivery',
        'price',
        'status',
        'current_period_start',
        'current_period_end',
        'next_delivery_date'
    ];

    protected $casts = [
        'bottles_per_delivery' => 'integer',
        'price' => 'decimal:2',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'next_delivery_date' => 'date'
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 关联订阅计划
     */
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * 关联配送记录
     */
    public function deliveries()
    {
        return $this->hasMany(SubscriptionDelivery::class);
    }
}




