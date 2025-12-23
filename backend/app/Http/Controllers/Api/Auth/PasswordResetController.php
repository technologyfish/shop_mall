<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordReset;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class PasswordResetController extends Controller
{
    /**
     * 发送验证码
     */
    public function sendCode(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|max:100',
        ], [
            'email.required' => 'Please enter your email address',
            'email.email' => 'Please enter a valid email address',
        ]);

        $email = $request->email;

        // 检查邮箱是否存在
        $user = User::where('email', $email)->first();
        if (!$user) {
            return $this->error('Email address not found', 404);
        }

        // 检查是否频繁请求（1分钟内只能发送一次）
        $recentCode = PasswordReset::where('email', $email)
            ->where('created_at', '>', Carbon::now()->subMinute())
            ->first();

        if ($recentCode) {
            return $this->error('Please wait 1 minute before requesting a new code', 429);
        }

        // 生成6位数字验证码
        $code = PasswordReset::generateCode();

        // 保存验证码（15分钟有效期）
        PasswordReset::create([
            'email' => $email,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(15),
            'is_used' => 0,
        ]);

        // 发送验证码邮件
        $sent = MailService::sendVerificationCode($email, $code);

        if (!$sent) {
            return $this->error('Failed to send verification code', 500);
        }

        return $this->success([
            'message' => 'Verification code sent successfully',
            'expires_in' => 900, // 15分钟 = 900秒
        ]);
    }

    /**
     * 验证验证码并重置密码
     */
    public function resetPassword(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|max:100',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:6|max:50',
        ], [
            'email.required' => 'Please enter your email address',
            'email.email' => 'Please enter a valid email address',
            'code.required' => 'Please enter the verification code',
            'code.size' => 'Verification code must be 6 digits',
            'password.required' => 'Please enter a new password',
            'password.min' => 'Password must be at least 6 characters',
        ]);

        $email = $request->email;
        $code = $request->code;
        $password = $request->password;

        // 查找最新的未使用的验证码
        $reset = PasswordReset::where('email', $email)
            ->where('code', $code)
            ->where('is_used', 0)
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$reset) {
            return $this->error('Invalid or expired verification code', 400);
        }

        // 查找用户
        $user = User::where('email', $email)->first();
        if (!$user) {
            return $this->error('User not found', 404);
        }

        // 更新密码
        $user->password = Hash::make($password);
        $user->save();

        // 标记验证码为已使用
        $reset->markAsUsed();

        // 使该邮箱的其他未使用验证码失效
        PasswordReset::where('email', $email)
            ->where('is_used', 0)
            ->where('id', '!=', $reset->id)
            ->update(['is_used' => 1]);

        return $this->success([
            'message' => 'Password reset successfully'
        ]);
    }

    /**
     * 验证验证码（可选，用于前端验证）
     */
    public function verifyCode(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $reset = PasswordReset::where('email', $request->email)
            ->where('code', $request->code)
            ->where('is_used', 0)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($reset) {
            return $this->success([
                'valid' => true,
                'message' => 'Verification code is valid'
            ]);
        }

        return $this->error('Invalid or expired verification code', 400);
    }
}




