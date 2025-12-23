<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    /**
     * 获取文章列表
     */
    public function index(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 10);
            $type = $request->input('type', 'article');

            $articles = Article::where('status', 1)
                ->where('type', $type)
                ->orderBy('sort', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($limit);

            return response()->json([
                'code' => 0,
                'message' => 'success',
                'data' => $articles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取文章详情
     */
    public function show($id)
    {
        try {
            $article = Article::find($id);

            if (!$article || $article->status != 1) {
                return response()->json([
                    'code' => 404,
                    'message' => 'Article not found'
                ], 404);
            }

            return response()->json([
                'code' => 0,
                'message' => 'success',
                'data' => $article
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 根据slug获取文章详情
     */
    public function getBySlug($slug)
    {
        try {
            $article = Article::getBySlug($slug);

            if (!$article) {
                return response()->json([
                    'code' => 404,
                    'message' => 'Article not found'
                ], 404);
            }

            return response()->json([
                'code' => 0,
                'message' => 'success',
                'data' => $article
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}






