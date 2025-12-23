<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * 用户列表
     */
    public function index(Request $request)
    {
        $query = User::query();

        // 关联订阅信息，用于标识订阅用户
        $query->withCount(['subscriptions as is_subscriber' => function($q) {
            $q->where('status', 'active');
        }]);

        // 搜索
        if ($request->has('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%")
                  ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }

        // 状态筛选
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // 订阅用户筛选
        if ($request->has('is_subscriber') && $request->is_subscriber !== null && $request->is_subscriber !== '') {
            if ($request->is_subscriber == 1) {
                // 只显示订阅用户
                $query->whereHas('subscriptions', function($q) {
                    $q->where('status', 'active');
                });
            } else {
                // 只显示非订阅用户
                $query->whereDoesntHave('subscriptions', function($q) {
                    $q->where('status', 'active');
                });
            }
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginate($users);
    }

    /**
     * 用户详情
     */
    public function show($id)
    {
        $user = User::with(['orders', 'addresses'])->find($id);

        if (!$user) {
            return $this->error('User not found', 404);
        }

        return $this->success($user);
    }

    /**
     * 更新用户状态
     */
    public function updateStatus(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->error('User not found', 404);
        }

        $user->status = $request->input('status', 1);
        $user->save();

        return $this->success($user, 'User status updated successfully');
    }
}


