<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * 分类列表
     */
    public function index()
    {
        $categories = Category::with('parent')
            ->orderBy('sort_order', 'asc')
            ->get();

        return $this->success($categories);
    }

    /**
     * 添加分类
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|exists:categories,id',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'integer',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $category = Category::create($request->all());

        return $this->success($category, 'Category created successfully', 201);
    }

    /**
     * 更新分类
     */
    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'parent_id' => 'nullable|exists:categories,id',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'integer',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // 防止设置自己为父分类
        if ($request->has('parent_id') && $request->parent_id == $id) {
            return $this->error('Cannot set self as parent', 400);
        }

        $category->update($request->all());

        return $this->success($category, 'Category updated successfully');
    }

    /**
     * 删除分类
     */
    public function delete($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        // 检查是否有子分类
        if ($category->children()->count() > 0) {
            return $this->error('Cannot delete category with children', 400);
        }

        // 检查是否有商品
        if ($category->products()->count() > 0) {
            return $this->error('Cannot delete category with products', 400);
        }

        $category->delete();

        return $this->success(null, 'Category deleted successfully');
    }
}







