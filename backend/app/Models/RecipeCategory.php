<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeCategory extends Model
{
    protected $table = 'recipe_categories';
    
    protected $fillable = [
        'name', 'slug', 'description', 'sort', 'status'
    ];

    protected $casts = [
        'sort' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 获取该分类下的所有菜谱
     */
    public function recipes()
    {
        return $this->hasMany(Recipe::class, 'category_id');
    }
}





