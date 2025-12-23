<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * 商品列表
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // 搜索
        if ($request->has('keyword')) {
            $keyword = $request->keyword;
            $query->where('name', 'like', "%{$keyword}%");
        }

        // 状态筛选
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // 精选筛选
        if ($request->has('is_featured') && $request->is_featured !== null && $request->is_featured !== '') {
            $query->where('is_featured', $request->is_featured);
        }

        $limit = $request->get('limit', 10);
        $products = $query->orderBy('sort', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        return $this->paginate($products);
    }

    /**
     * 商品详情
     */
    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->error('Product not found', 404);
        }

        return $this->success($product);
    }

    /**
     * 添加商品
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
            'slug' => 'required|string|max:200|unique:products,slug',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'image' => 'nullable|string',
            'images' => 'nullable|array',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'sales' => 'nullable|integer|min:0',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'tags' => 'nullable|array',
            'sort' => 'nullable|integer',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $product = Product::create($request->all());

        return $this->success($product, 'Product created successfully', 201);
    }

    /**
     * 更新商品
     */
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->error('Product not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:200',
            'slug' => 'sometimes|string|max:200|unique:products,slug,' . $id,
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'image' => 'nullable|string',
            'images' => 'nullable|array',
            'price' => 'sometimes|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'sales' => 'nullable|integer|min:0',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'tags' => 'nullable|array',
            'sort' => 'nullable|integer',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $product->update($request->all());

        return $this->success($product, 'Product updated successfully');
    }

    /**
     * 删除商品
     */
    public function delete($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->error('Product not found', 404);
        }

        $product->delete();

        return $this->success(null, 'Product deleted successfully');
    }

    /**
     * 更新商品状态
     */
    public function updateStatus(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->error('Product not found', 404);
        }

        $product->status = $request->input('status', 1);
        $product->save();

        return $this->success($product, 'Product status updated successfully');
    }
}


