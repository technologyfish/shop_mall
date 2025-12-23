<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    /**
     * 获取订阅计划列表
     */
    public function index(Request $request)
    {
        $query = SubscriptionPlan::query();

        // 搜索
        if ($request->has('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // 状态筛选
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $plans = $query->orderBy('sort', 'asc')
            ->orderBy('price', 'asc')
            ->paginate($request->get('per_page', 15));

        return $this->paginate($plans);
    }

    /**
     * 获取单个计划
     */
    public function show($id)
    {
        $plan = SubscriptionPlan::find($id);

        if (!$plan) {
            return $this->error('Plan not found', 404);
        }

        return $this->success($plan);
    }

    /**
     * 创建订阅计划
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'stripe_price_id' => 'nullable|string|max:255',
            'plan_type' => 'required|string|in:monthly,quarterly,yearly',
            'bottles_per_delivery' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'delivery_day' => 'nullable|integer|min:1|max:28',
            'status' => 'nullable|boolean',
            'sort' => 'nullable|integer',
        ]);

        $plan = SubscriptionPlan::create($request->all());

        return $this->success($plan, 'Subscription plan created successfully', 201);
    }

    /**
     * 更新订阅计划
     */
    public function update(Request $request, $id)
    {
        $plan = SubscriptionPlan::find($id);

        if (!$plan) {
            return $this->error('Plan not found', 404);
        }

        $this->validate($request, [
            'name' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
            'stripe_price_id' => 'nullable|string|max:255',
            'plan_type' => 'sometimes|required|string|in:monthly,quarterly,yearly',
            'bottles_per_delivery' => 'sometimes|required|integer|min:1',
            'price' => 'sometimes|required|numeric|min:0',
            'delivery_day' => 'nullable|integer|min:1|max:28',
            'status' => 'nullable|boolean',
            'sort' => 'nullable|integer',
        ]);

        $plan->update($request->all());

        return $this->success($plan, 'Subscription plan updated successfully');
    }

    /**
     * 删除订阅计划
     */
    public function destroy($id)
    {
        $plan = SubscriptionPlan::find($id);

        if (!$plan) {
            return $this->error('Plan not found', 404);
        }

        // 检查是否有活跃订阅使用此计划
        $activeSubscriptions = $plan->subscriptions()
            ->where('status', 'active')
            ->count();

        if ($activeSubscriptions > 0) {
            return $this->error('Cannot delete plan with active subscriptions', 400);
        }

        $plan->delete();

        return $this->success(null, 'Subscription plan deleted successfully');
    }
}




