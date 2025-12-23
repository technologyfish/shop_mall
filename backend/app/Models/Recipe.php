<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $fillable = [
        'category_id',
        'title',
        'subtitle',
        'slug',
        'image',
        'description',
        'ingredients',
        'instructions',
        'prep_time',
        'cook_time',
        'servings',
        'difficulty',
        'is_featured',
        'sort',
        'status'
    ];

    protected $casts = [
        'category_id' => 'integer',
        'status' => 'boolean',
        'is_featured' => 'boolean',
        'sort' => 'integer',
        'prep_time' => 'integer',
        'cook_time' => 'integer',
        'servings' => 'integer'
    ];

    /**
     * 获取所属分类
     */
    public function category()
    {
        return $this->belongsTo(RecipeCategory::class, 'category_id');
    }

    /**
     * 获取精选食谱
     */
    public static function getFeatured($limit = null)
    {
        $query = self::where('status', 1)
            ->where('is_featured', 1)
            ->orderBy('sort', 'desc')
            ->orderBy('id', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * 根据slug获取食谱
     */
    public static function getBySlug($slug)
    {
        return self::where('slug', $slug)
            ->where('status', 1)
            ->first();
    }
}


