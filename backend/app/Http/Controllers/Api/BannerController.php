<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * Banner列表
     */
    public function index(Request $request)
    {
        // 获取启用状态的Banner
        $banners = Banner::where('status', 1)
            ->orderBy('sort', 'desc')
            ->get();

        return $this->success($banners);
    }
}






