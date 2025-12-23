<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    protected $fillable = [
        'name',
        'discount_type',
        'discount_value',
        'status'
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'status' => 'integer',
    ];

    /**
     * 获取当前启用的促销活动（用于弹窗）
     */
    public static function getActivePromotion()
    {
        return self::where('status', self::STATUS_ACTIVE)->first();
    }

    /**
     * 计算折扣金额
     * @param float $orderAmount 订单金额
     * @return float 折扣金额
     */
    public function calculateDiscount($orderAmount)
    {
        if ($this->discount_type === 'percentage') {
            return $orderAmount * ($this->discount_value / 100);
        }
        
        // 其他折扣类型可以在这里扩展
        return 0;
    }
}
