<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordReset;
use App\Models\EmailTask;
use App\Models\MailTransferSubmission;
use App\Models\Order;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * 用户注册
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:100|unique:users',
            'nickname' => 'nullable|string|max:100',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20|unique:users',
            'avatar' => 'nullable|string|max:500',
            'off_code' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // 验证 off_code（如果提供）
        $offCodeSubmission = null;
        $firstOrderDiscount = 0;
        $offCodeValue = null;
        if ($request->off_code) {
            $code = strtoupper(trim($request->off_code));
            $offCodeSubmission = MailTransferSubmission::where('off_code', $code)->first();
            
            if (!$offCodeSubmission) {
                return response()->json([
                    'code' => 400,
                    'message' => 'Invalid off code',
                    'errors' => ['off_code' => ['The off code is invalid.']]
                ], 400);
            }
            
            // 检查是否已被使用（关联的用户是否已下单）
            if ($offCodeSubmission->user_id) {
                $hasOrder = Order::where('user_id', $offCodeSubmission->user_id)
                    ->where('status', '!=', Order::STATUS_CANCELLED)
                    ->exists();
                if ($hasOrder) {
                    return response()->json([
                        'code' => 400,
                        'message' => 'Off code already used',
                        'errors' => ['off_code' => ['This off code has already been used.']]
                    ], 400);
                }
            }
            
            // off_code 有效，激活首单优惠
            $firstOrderDiscount = 1;
            $offCodeValue = $offCodeSubmission->off_code;
        }

        try {
            $user = User::create([
                'username' => $request->username,
                'nickname' => $request->nickname ?: $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'avatar' => $request->avatar,
                'password' => Hash::make($request->password),
                'status' => 1,
                'first_order_discount' => $firstOrderDiscount,
                'off_code' => $offCodeValue
            ]);
            
            // 标记 off_code 关联用户
            if ($offCodeSubmission) {
                $offCodeSubmission->update([
                    'user_id' => $user->id
                ]);
                
                \Log::info('Off code used for registration', [
                    'user_id' => $user->id,
                    'off_code' => $offCodeSubmission->off_code
                ]);
            }

            // 触发注册欢迎邮件（不影响注册流程）
            $this->sendWelcomeEmail($user);

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'code' => 0,
                'message' => 'Registration successful',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Registration failed', ['error' => $e->getMessage()]);
            return response()->json([
                'code' => 500,
                'message' => 'Registration failed: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * 用户登录（支持用户名或邮箱）
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // 判断是邮箱还是用户名
        $field = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [
            $field => $request->username,
            'password' => $request->password
        ];

        // 先检查用户是否存在
        $user = User::where($field, $request->username)->first();
        if (!$user) {
            return response()->json([
                'code' => 404,
                'message' => 'User not found. Please register first.',
                'error_type' => 'user_not_found'
            ], 404);
        }

        if (!$token = auth()->attempt($credentials)) {
            return response()->json([
                'code' => 401,
                'message' => 'Incorrect password. Please try again.'
            ], 401);
        }

        $user = auth()->user();

        if ($user->status != 1) {
            return response()->json([
                'code' => 403,
                'message' => 'Account is disabled'
            ], 403);
        }

        return response()->json([
            'code' => 0,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ]);
    }

    /**
     * 获取当前用户信息
     */
    public function me()
    {
        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => auth()->user()
        ]);
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        auth()->logout();
        return response()->json([
            'code' => 0,
            'message' => 'Logout successful'
        ]);
    }

    /**
     * 更新个人资料
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'nickname' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only(['nickname', 'phone', 'avatar']));

        return response()->json([
            'code' => 0,
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    }

    /**
     * 更新头像
     */
    public function updateAvatar(Request $request)
    {
        if (!$request->hasFile('file')) {
             return response()->json(['code' => 400, 'message' => 'No file uploaded'], 400);
        }
        $file = $request->file('file');
        if (!$file->isValid()) {
             return response()->json(['code' => 400, 'message' => 'Invalid file'], 400);
        }

        $extension = $file->getClientOriginalExtension();
        $filename = 'avatar_' . auth()->id() . '_' . time() . '.' . $extension;
        $path = 'uploads/avatars/' . date('Y-m-d');
        $fullPath = base_path('public/' . $path);

        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0777, true);
        }

        $file->move($fullPath, $filename);
        $url = '/' . $path . '/' . $filename;

        $user = auth()->user();
        $user->avatar = $url;
        $user->save();

        return response()->json([
            'code' => 0,
            'message' => 'Avatar updated successfully',
            'data' => $user
        ]);
    }

    /**
     * 修改密码
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'code' => 400,
                'message' => 'Old password is incorrect'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'code' => 0,
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * 忘记密码
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // 生成重置令牌
        $token = bin2hex(random_bytes(32));

        // 这里应该发送邮件，暂时返回token
        // TODO: 实现邮件发送功能

        return $this->success([
            'reset_token' => $token
        ], 'Password reset email sent');
    }

    /**
     * 重置密码
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // TODO: 验证token
        // 这里简化处理，实际应该验证token的有效性

        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return $this->success(null, 'Password reset successfully');
    }

    /**
     * 发送欢迎邮件
     */
    private function sendWelcomeEmail($user)
    {
        try {
            \Log::info('Attempting to send welcome email', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            $task = EmailTask::where('type', EmailTask::TYPE_WELCOME)
                ->where('status', EmailTask::STATUS_ENABLED)
                ->first();

            if (!$task) {
                \Log::warning('Welcome email task not found or disabled. Please create a welcome email task (type=1) in admin panel.');
                return;
            }
            
            if (!$user->email) {
                \Log::warning('User has no email address', ['user_id' => $user->id]);
                return;
            }

            $variables = [
                '{username}' => $user->username,
                '{nickname}' => $user->nickname ?? $user->username,
                '{email}' => $user->email,
                '{login_link}' => env('APP_FRONTEND_URL', 'http://localhost:5173') . '/login',
            ];
            $subject = str_replace(array_keys($variables), array_values($variables), $task->subject);
            $body = str_replace(array_keys($variables), array_values($variables), $task->content);

            $result = MailService::sendHtmlMail($user->email, $subject, $body);
            
            \Log::info('Welcome email sent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'subject' => $subject,
                'result' => $result
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send welcome email', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            // 不影响注册流程，继续执行
        }
    }
}


