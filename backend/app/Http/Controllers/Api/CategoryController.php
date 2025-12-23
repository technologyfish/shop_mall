<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * 获取分类列表
     */
    public function index()
    {
        $categories = Category::where('status', 1)
            ->orderBy('sort_order', 'asc')
            ->get();

        // 构建树形结构
        $tree = $this->buildTree($categories->toArray());

        return $this->success($tree);
    }

    /**
     * 构建树形结构
     */
    private function buildTree($items, $parentId = 0)
    {
        $tree = [];
        foreach ($items as $item) {
            if ($item['parent_id'] == $parentId) {
                $children = $this->buildTree($items, $item['id']);
                if ($children) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }
}







