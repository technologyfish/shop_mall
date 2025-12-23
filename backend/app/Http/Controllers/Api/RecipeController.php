<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    /**
     * 获取食谱列表
     */
    public function index(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 12);
            $difficulty = $request->input('difficulty');

            $query = Recipe::where('status', 1);

            if ($difficulty) {
                $query->where('difficulty', $difficulty);
            }

            $recipes = $query->orderBy('sort', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($limit);

            return response()->json([
                'code' => 0,
                'message' => 'success',
                'data' => $recipes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取食谱详情
     */
    public function show($id)
    {
        try {
            $recipe = Recipe::find($id);

            if (!$recipe || $recipe->status != 1) {
                return response()->json([
                    'code' => 404,
                    'message' => 'Recipe not found'
                ], 404);
            }

            return response()->json([
                'code' => 0,
                'message' => 'success',
                'data' => $recipe
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 根据slug获取食谱详情
     */
    public function getBySlug($slug)
    {
        try {
            $recipe = Recipe::getBySlug($slug);

            if (!$recipe) {
                return response()->json([
                    'code' => 404,
                    'message' => 'Recipe not found'
                ], 404);
            }

            return response()->json([
                'code' => 0,
                'message' => 'success',
                'data' => $recipe
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取食谱分类及其食谱列表
     */
    public function categories(Request $request)
    {
        try {
            $categories = \App\Models\RecipeCategory::with(['recipes' => function($query) {
                $query->where('status', 1)
                      ->orderBy('sort', 'desc')
                      ->orderBy('id', 'desc');
            }])
            ->where('status', 1)
            ->orderBy('sort', 'desc')
            ->orderBy('id', 'desc')
            ->get();

            return response()->json([
                'code' => 0,
                'message' => 'success',
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}






