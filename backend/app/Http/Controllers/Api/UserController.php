<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDiscount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * 获取当前用户信息
     */
    public function show(Request $request)
    {
        $user = auth()->user();
        return $this->success($user);
    }

    /**
     * 更新用户信息
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'nickname' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        if ($request->has('nickname')) {
            $user->nickname = $request->nickname;
        }

        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }

        if ($request->has('avatar')) {
            $user->avatar = $request->avatar;
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return $this->success($user, 'Profile updated successfully');
    }

    /**
     * 获取用户可用折扣
     */
    public function getDiscount(Request $request)
    {
        $user = auth()->user();
        $discount = UserDiscount::getAvailableDiscountForUser($user->id);
        
        if ($discount) {
            return $this->success([
                'has_discount' => true,
                'discount_value' => $discount->discount_value,
                'promotion_id' => $discount->promotion_id,
                'created_at' => $discount->created_at
            ]);
        }
        
        return $this->success([
            'has_discount' => false,
            'discount_value' => 0
        ]);
    }
}



