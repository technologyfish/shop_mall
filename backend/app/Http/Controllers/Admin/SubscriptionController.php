<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionDelivery;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * 获取订阅列表
     */
    public function index(Request $request)
    {
        $query = Subscription::with(['user', 'plan']);

        // 搜索
        if ($request->has('keyword')) {
            $keyword = $request->keyword;
            $query->whereHas('user', function($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        // 状态筛选
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $subscriptions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginate($subscriptions);
    }

    /**
     * 获取订阅详情
     */
    public function show($id)
    {
        $subscription = Subscription::with(['user', 'plan', 'deliveries'])->find($id);

        if (!$subscription) {
            return $this->error('Subscription not found', 404);
        }

        return $this->success($subscription);
    }

}

