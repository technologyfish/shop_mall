<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $recipes = Recipe::with('category')
                ->orderBy('sort', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($request->input('limit', 20));

            return response()->json(['code' => 0, 'message' => 'success', 'data' => $recipes]);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'slug' => 'nullable|string|max:255|unique:recipes',
            'image' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'ingredients' => 'nullable|string',
            'instructions' => 'nullable|string',
            'cook_time' => 'nullable|integer',
            'servings' => 'nullable|integer',
            'difficulty' => 'nullable|string|in:easy,medium,hard',
            'is_featured' => 'nullable|boolean',
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
            
            $recipe = Recipe::create($data);
            return response()->json(['code' => 0, 'message' => 'Recipe created successfully', 'data' => $recipe], 201);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $recipe = Recipe::findOrFail($id);
            $data = $request->all();
            
            if (isset($data['title']) && empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }
            
            $recipe->update($data);
            return response()->json(['code' => 0, 'message' => 'Recipe updated successfully', 'data' => $recipe]);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            Recipe::findOrFail($id)->delete();
            return response()->json(['code' => 0, 'message' => 'Recipe deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }
}


