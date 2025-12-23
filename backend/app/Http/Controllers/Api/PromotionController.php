<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promotion;

class PromotionController extends Controller
{
    /**
     * 获取当前启用的促销活动
     */
    public function getActivePromotion()
    {
        $promotion = Promotion::getActivePromotion();
        
        if (!$promotion) {
            return $this->success(null);
        }
        
        return $this->success($promotion);
    }
}



