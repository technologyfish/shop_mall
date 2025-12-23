<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'order_no', 'payment_no', 'pay_method',
        'pay_amount', 'status', 'pay_time', 'callback_data', 'return_url'
    ];

    protected $casts = [
        'pay_amount' => 'decimal:2',
        'pay_time' => 'datetime',
    ];

    // 支付状态
    const STATUS_PENDING = 0;  // 待支付
    const STATUS_SUCCESS = 1;  // 支付成功
    const STATUS_FAILED = 2;   // 支付失败

    /**
     * 所属订单
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}




