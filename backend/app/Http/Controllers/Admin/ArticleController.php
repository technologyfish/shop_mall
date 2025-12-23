<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        try {
            $type = $request->input('type');
            $query = Article::query();
            
            if ($type) {
                $query->where('type', $type);
            }
            
            $articles = $query->orderBy('sort', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($request->input('limit', 20));

            return response()->json(['code' => 0, 'message' => 'success', 'data' => $articles]);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'slug' => 'nullable|string|max:255|unique:articles',
            'image' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'type' => 'nullable|string|max:50',
            'sort' => 'nullable|integer',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['code' => 422, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $data = $request->all();
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }
            
            $article = Article::create($data);
            return response()->json(['code' => 0, 'message' => 'Article created successfully', 'data' => $article], 201);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $article = Article::findOrFail($id);
            $data = $request->all();
            
            if (isset($data['title']) && empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }
            
            $article->update($data);
            return response()->json(['code' => 0, 'message' => 'Article updated successfully', 'data' => $article]);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            Article::findOrFail($id)->delete();
            return response()->json(['code' => 0, 'message' => 'Article deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }
}






