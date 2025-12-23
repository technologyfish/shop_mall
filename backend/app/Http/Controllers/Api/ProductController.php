<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * 商品列表
     */
    public function index(Request $request)
    {
        $query = Product::where('status', 1);

        // 搜索
        if ($request->has('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // 筛选热卖/新品/精选
        if ($request->has('is_hot') && $request->is_hot) {
            $query->where('is_hot', 1);
        }
        if ($request->has('is_new') && $request->is_new) {
            $query->where('is_new', 1);
        }
        if ($request->has('is_featured') && $request->is_featured) {
            $query->where('is_featured', 1);
        }

        // 排序
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分页
        $limit = $request->get('limit', 15); // 兼容前端 limit 参数
        $perPage = $request->get('per_page', $limit);
        $products = $query->paginate($perPage);

        return $this->paginate($products);
    }

    /**
     * 商品详情
     */
    public function show($id)
    {
        $product = Product::find($id);

        if (!$product || $product->status != 1) {
            return $this->error('Product not found', 404);
        }

        return $this->success($product);
    }
}


