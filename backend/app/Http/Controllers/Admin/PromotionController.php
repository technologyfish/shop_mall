<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    /**
     * 促销活动列表
     */
    public function index(Request $request)
    {
        $query = Promotion::query();

        if ($request->has('keyword')) {
            $query->where('name', 'like', "%{$request->keyword}%");
        }

        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $promotions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginate($promotions);
    }

    /**
     * 创建促销活动
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:100',
            'discount_type' => 'required|string|max:50',
            'discount_value' => 'required|numeric|min:0',
            'require_mail_transfer' => 'boolean',
            'target_users' => 'required|in:registered,unregistered,all',
            'popup_enabled' => 'boolean',
            'status' => 'required|boolean'
        ]);

        $promotion = Promotion::create($request->all());

        return $this->success($promotion, '创建成功');
    }

    /**
     * 更新促销活动
     */
    public function update(Request $request, $id)
    {
        $promotion = Promotion::findOrFail($id);

        $this->validate($request, [
            'name' => 'required|string|max:100',
            'discount_type' => 'nullable|string|max:50',
            'discount_value' => 'required|numeric|min:0',
            'require_mail_transfer' => 'nullable|boolean',
            'target_users' => 'nullable|in:registered,unregistered,all',
            'popup_enabled' => 'nullable|boolean',
            'status' => 'required|boolean'
        ]);

        // 只更新提供的字段
        $updateData = $request->only(['name', 'discount_type', 'discount_value', 'status', 'require_mail_transfer', 'target_users', 'popup_enabled']);
        $promotion->update($updateData);

        return $this->success($promotion, '更新成功');
    }

    /**
     * 删除促销活动
     */
    public function destroy($id)
    {
        $promotion = Promotion::findOrFail($id);
        $promotion->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 获取促销活动详情
     */
    public function show($id)
    {
        $promotion = Promotion::findOrFail($id);
        return $this->success($promotion);
    }
}
