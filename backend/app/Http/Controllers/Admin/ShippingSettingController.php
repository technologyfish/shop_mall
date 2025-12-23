<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingSetting;
use Illuminate\Http\Request;

class ShippingSettingController extends Controller
{
    /**
     * 获取运费设置
     */
    public function index()
    {
        $setting = ShippingSetting::first();
        
        if (!$setting) {
            // 如果没有设置，创建默认设置
            $setting = ShippingSetting::create([
                'shipping_fee' => 5.99,
                'free_shipping_threshold' => 50.00,
                'status' => 1
            ]);
        }

        return $this->success($setting);
    }

    /**
     * 更新运费设置
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'shipping_fee' => 'required|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'status' => 'required|boolean'
        ]);

        $setting = ShippingSetting::first();

        if (!$setting) {
            $setting = new ShippingSetting();
        }

        $setting->fill($request->only(['shipping_fee', 'free_shipping_threshold', 'status']));
        $setting->save();

        return $this->success($setting, '更新成功');
    }
}



