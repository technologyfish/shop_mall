<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecipeCategory;

class RecipeCategoryController extends Controller
{
    /**
     * 获取所有分类（公开接口）
     */
    public function index()
    {
        $categories = RecipeCategory::where('status', 1)
            ->orderBy('sort', 'asc')
            ->withCount('recipes')
            ->with(['recipes' => function($query) {
                $query->where('status', 1)
                      ->orderBy('sort', 'desc')
                      ->orderBy('id', 'desc')
                      ->limit(4); // 每个分类只显示4个菜谱
            }])
            ->get();

        return $this->success($categories);
    }
}

