<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class OrderController extends Controller
{
    /**
     * 订单列表
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.product', 'subscription']);

        // 处理订阅订单筛选
        if ($request->has('is_subscription')) {
            $isSubscription = $request->is_subscription;
            if ($isSubscription == 1) {
                // 显示订阅订单
                $query->where('is_subscription', 1);
            } else if ($isSubscription == 0) {
                // 显示普通订单
                $query->where('is_subscription', 0);
            }
            // 如果 is_subscription 为空或其他值，不过滤，显示所有订单
        } else {
            // 默认只显示普通订单（过滤掉订阅订单）
            $query->where('is_subscription', 0);
        }

        // 搜索
        if ($request->has('keyword') && $request->keyword != '') {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('order_no', 'like', "%{$keyword}%")
                  ->orWhere('shipping_name', 'like', "%{$keyword}%")
                  ->orWhere('shipping_phone', 'like', "%{$keyword}%");
            });
        }

        // 状态筛选
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // 支付状态筛选
        if ($request->has('pay_status') && $request->pay_status !== null && $request->pay_status !== '') {
            $query->where('pay_status', $request->pay_status);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginate($orders);
    }

    /**
     * 订单详情
     */
    public function show($id)
    {
        $order = Order::with(['user', 'items.product', 'payment'])->find($id);

        if (!$order) {
            return $this->error('Order not found', 404);
        }

        return $this->success($order);
    }

    /**
     * 发货/更新物流
     * 状态流转：待发货(1) -> 已完成(3)
     */
    public function ship(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->error('Order not found', 404);
        }

        // 只有待发货的订单才能填写物流
        if ($order->status != 1) {
            return $this->error('Only orders with status "待发货" can be shipped', 400);
        }

        // 验证请求参数（Lumen使用$this->validate）
        $this->validate($request, [
            'status' => 'required|integer|in:3', // 只能改为已完成(3)
            'shipping_company' => 'required|string|max:50',
            'shipping_no' => 'required|string|max:50',
        ]);

        try {
            // 更新为已完成状态 - 只更新数据库中存在的列
            $order->status = 3;
            $order->shipping_company = $request->shipping_company;
            $order->shipping_no = $request->shipping_no;
            $order->save();

            return $this->success($order, 'Order shipping info updated successfully');
        } catch (\Exception $e) {
            \Log::error('Order ship failed', ['error' => $e->getMessage(), 'order_id' => $id]);
            return $this->error('Failed to update order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 统计数据
     */
    public function statistics()
    {
        $data = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', Order::STATUS_PENDING)->count(),
            'paid_orders' => Order::where('status', Order::STATUS_PAID)->count(),
            'shipped_orders' => Order::where('status', Order::STATUS_SHIPPED)->count(),
            'completed_orders' => Order::where('status', Order::STATUS_COMPLETED)->count(),
            'total_amount' => Order::where('pay_status', 1)->sum('pay_amount'),
            'today_orders' => Order::whereDate('created_at', Carbon::today())->count(),
            'today_amount' => Order::whereDate('created_at', Carbon::today())
                ->where('pay_status', 1)
                ->sum('pay_amount'),
        ];

        return $this->success($data);
    }
}


