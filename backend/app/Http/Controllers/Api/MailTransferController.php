<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MailTransferSubmission;
use App\Models\Promotion;
use App\Models\UserDiscount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MailTransferController extends Controller
{
    /**
     * 提交MailTransfer表单
     */
    public function submit(Request $request)
    {
        $this->validate($request, [
            'nickname' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'promotion_id' => 'nullable|integer|exists:promotions,id'
        ]);

        // 检查是否已提交
        if (MailTransferSubmission::hasSubmitted($request->email)) {
            return $this->error('This email has already been submitted', 400);
        }

        $userId = Auth::id();

        $submission = MailTransferSubmission::create([
            'nickname' => $request->nickname,
            'email' => $request->email,
            'user_id' => $userId,
            'promotion_id' => $request->promotion_id
        ]);

        // 如果用户已登录且有促销活动ID，为用户创建折扣记录
        if ($userId && $request->promotion_id) {
            $promotion = Promotion::find($request->promotion_id);
            if ($promotion && $promotion->status == 1) {
                // 检查用户是否已有该促销的折扣
                $existingDiscount = UserDiscount::where('user_id', $userId)
                    ->where('promotion_id', $promotion->id)
                    ->first();
                
                if (!$existingDiscount) {
                    $discount = UserDiscount::create([
                        'user_id' => $userId,
                        'promotion_id' => $promotion->id,
                        'discount_value' => $promotion->discount_value,
                        'is_used' => false
                    ]);
                    
                    \Log::info('User discount created', [
                        'user_id' => $userId,
                        'promotion_id' => $promotion->id,
                        'discount_value' => $promotion->discount_value,
                        'discount_id' => $discount->id
                    ]);
                } else {
                    \Log::info('User already has discount for this promotion', [
                        'user_id' => $userId,
                        'promotion_id' => $promotion->id
                    ]);
                }
            }
        } else {
            \Log::warning('User discount not created', [
                'user_id' => $userId,
                'promotion_id' => $request->promotion_id,
                'reason' => !$userId ? 'User not logged in' : 'No promotion_id provided'
            ]);
        }

        return $this->success($submission, 'Submitted successfully');
    }

    /**
     * 检查邮箱是否已提交
     */
    public function checkSubmission(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email'
        ]);

        $hasSubmitted = MailTransferSubmission::hasSubmitted($request->email);

        return $this->success([
            'has_submitted' => $hasSubmitted
        ]);
    }

    /**
     * 获取当前弹窗促销活动
     */
    public function getActivePromotion()
    {
        $promotion = Promotion::getActivePopupPromotion();
        
        if (!$promotion) {
            return $this->success(null);
        }

        return $this->success($promotion);
    }
}

