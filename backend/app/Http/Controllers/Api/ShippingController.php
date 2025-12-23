<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingSetting;

class ShippingController extends Controller
{
    /**
     * 获取运费设置（公开接口）
     */
    public function getSettings()
    {
        $setting = ShippingSetting::where('status', 1)->first();
        
        if (!$setting) {
            // 如果没有设置，返回默认值
            $setting = [
                'shipping_fee' => 0.00,
                'free_shipping_threshold' => null,
                'status' => 1
            ];
        }

        return $this->success($setting);
    }
}






