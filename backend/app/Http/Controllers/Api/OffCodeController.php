<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MailTransferSubmission;
use App\Models\Promotion;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OffCodeController extends Controller
{
    /**
     * 生成唯一的优惠码
     */
    private function generateCode()
    {
        $prefix = 'OFF10';
        $randomPart = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        $code = $prefix . $randomPart;
        
        // 确保唯一性
        while (MailTransferSubmission::where('off_code', $code)->exists()) {
            $randomPart = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
            $code = $prefix . $randomPart;
        }
        
        return $code;
    }

    /**
     * 收集邮件并生成优惠码
     * 如果邮箱已存在，返回已有的优惠码
     */
    public function collect(Request $request)
    {
        $this->validate($request, [
            'nickname' => 'required|string|max:100',
            'email' => 'required|email|max:255'
        ]);

        try {
            $email = strtolower(trim($request->email));
            
            // 检查邮箱是否已获取过优惠码（检查 off_code 字段是否存在）
            $existingSubmission = MailTransferSubmission::where('email', $email)->first();
            
            if ($existingSubmission && $existingSubmission->off_code) {
                // 已存在，直接返回
                return $this->success([
                    'off_code' => $existingSubmission->off_code,
                    'is_existing' => true
                ], 'You already have an off code!');
            }

            // 生成新的优惠码
            $code = $this->generateCode();
            
            if ($existingSubmission) {
                // 更新现有记录
                $existingSubmission->off_code = $code;
                $existingSubmission->nickname = $request->nickname;
                $existingSubmission->save();
                $submission = $existingSubmission;
            } else {
                // 创建新记录到 mail_transfer_submissions 表
                $submission = MailTransferSubmission::create([
                    'nickname' => $request->nickname,
                    'email' => $email,
                    'off_code' => $code,
                    'promotion_id' => 1  // 首单优惠活动ID
                ]);
            }

            return $this->success([
                'off_code' => $submission->off_code,
                'is_existing' => false
            ], 'Off code generated successfully!');
            
        } catch (\Exception $e) {
            \Log::error('Off code collect error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Failed to generate off code: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 验证优惠码是否有效
     */
    public function verify(Request $request)
    {
        $this->validate($request, [
            'code' => 'required|string|max:50'
        ]);

        $code = strtoupper(trim($request->code));
        $submission = MailTransferSubmission::where('off_code', $code)->first();

        if (!$submission) {
            return $this->error('Invalid off code', 400);
        }

        // 检查是否已被使用（关联的用户是否已下单）
        if ($submission->user_id) {
            $hasOrder = Order::where('user_id', $submission->user_id)
                ->where('status', '!=', Order::STATUS_CANCELLED)
                ->exists();
            if ($hasOrder) {
                return $this->error('This off code has already been used', 400);
            }
        }

        // 获取首单优惠活动（ID=1）的折扣值
        $promotion = Promotion::find(1);
        $discountValue = $promotion ? $promotion->discount_value : 10;

        return $this->success([
            'valid' => true,
            'discount_value' => $discountValue
        ], 'Off code is valid!');
    }

    /**
     * 获取首单优惠促销信息
     */
    public function getFirstOrderPromotion()
    {
        $promotion = Promotion::find(1);
        
        if (!$promotion || $promotion->status != 1) {
            return $this->success([
                'active' => false,
                'discount_value' => 0
            ]);
        }

        return $this->success([
            'active' => true,
            'discount_value' => $promotion->discount_value,
            'name' => $promotion->name
        ]);
    }
    
    /**
     * 检查当前用户是否应该显示促销弹窗
     */
    public function checkShowPopup(Request $request)
    {
        $userId = Auth::id();
        $email = $request->get('email');
        
        // 如果未登录，总是可以显示
        if (!$userId) {
            // 如果提供了邮箱，检查是否已提交过
            if ($email) {
                $hasSubmitted = MailTransferSubmission::where('email', strtolower(trim($email)))
                    ->whereNotNull('off_code')
                    ->exists();
                return $this->success(['show' => !$hasSubmitted]);
            }
            return $this->success(['show' => true]);
        }
        
        // 登录用户：检查是否有已支付的订单
        $hasOrder = Order::where('user_id', $userId)
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->where('pay_status', 1)
            ->exists();
        
        if ($hasOrder) {
            return $this->success(['show' => false, 'reason' => 'has_order']);
        }
        
        // 检查是否已提交过
        $user = Auth::user();
        if ($user && $user->email) {
            $hasSubmitted = MailTransferSubmission::where('email', strtolower($user->email))
                ->whereNotNull('off_code')
                ->exists();
            if ($hasSubmitted) {
                return $this->success(['show' => false, 'reason' => 'already_submitted']);
            }
        }
        
        return $this->success(['show' => true]);
    }
}



