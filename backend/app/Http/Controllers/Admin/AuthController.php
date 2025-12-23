<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * 管理员登录
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $credentials = $request->only(['username', 'password']);

        if (!$token = auth('admin')->attempt($credentials)) {
            return $this->error('Invalid credentials', 401);
        }

        $admin = auth('admin')->user();

        if ($admin->status != 1) {
            return $this->error('Account is disabled', 403);
        }

        return $this->success([
            'admin' => $admin,
            'token' => $token
        ], 'Login successful');
    }

    /**
     * 获取当前管理员信息
     */
    public function user()
    {
        return $this->success(auth('admin')->user());
    }
    
    /**
     * 获取当前管理员信息（别名）
     */
    public function me()
    {
        return $this->user();
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        auth('admin')->logout();
        return $this->success(null, 'Logout successful');
    }
}







