<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecipeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RecipeCategoryController extends Controller
{
    /**
     * 分类列表
     */
    public function index(Request $request)
    {
        $query = RecipeCategory::query();

        // 搜索
        if ($request->has('keyword') && $request->keyword) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // 状态筛选
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $categories = $query->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->withCount('recipes')
            ->paginate($request->get('per_page', 15));

        return $this->paginate($categories);
    }

    /**
     * 分类详情
     */
    public function show($id)
    {
        $category = RecipeCategory::with('recipes')->find($id);
        
        if (!$category) {
            return $this->error('Category not found', 404);
        }
        
        return $this->success($category);
    }

    /**
     * 创建分类
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:recipe_categories,slug',
            'description' => 'nullable|string',
            'sort' => 'nullable|integer',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            
            // 如果没有提供slug，自动生成
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $category = RecipeCategory::create($data);
            
            return $this->success($category, 'Category created successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * 更新分类
     */
    public function update($id, Request $request)
    {
        $category = RecipeCategory::find($id);
        
        if (!$category) {
            return $this->error('Category not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:100',
            'slug' => 'sometimes|required|string|max:100|unique:recipe_categories,slug,' . $id,
            'description' => 'nullable|string',
            'sort' => 'nullable|integer',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        try {
            $category->update($request->all());
            return $this->success($category, 'Category updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * 删除分类
     */
    public function delete($id)
    {
        $category = RecipeCategory::find($id);
        
        if (!$category) {
            return $this->error('Category not found', 404);
        }

        // 检查是否有菜谱使用此分类
        if ($category->recipes()->count() > 0) {
            return $this->error('Cannot delete category with recipes', 400);
        }

        $category->delete();
        return $this->success(null, 'Category deleted successfully');
    }
}





