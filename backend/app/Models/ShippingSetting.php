<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingSetting extends Model
{
    protected $fillable = [
        'shipping_fee',
        'free_shipping_threshold',
        'status'
    ];

    protected $casts = [
        'shipping_fee' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'status' => 'integer',
    ];

    /**
     * 获取当前启用的运费设置
     */
    public static function getActiveSetting()
    {
        return self::where('status', 1)->first();
    }

    /**
     * 计算运费
     * @param float $orderAmount 订单金额
     * @return float 运费
     */
    public function calculateShippingFee($orderAmount)
    {
        // 如果订单金额达到免运费门槛，运费为0
        if ($this->free_shipping_threshold && $orderAmount >= $this->free_shipping_threshold) {
            return 0;
        }
        
        return $this->shipping_fee;
    }
}



