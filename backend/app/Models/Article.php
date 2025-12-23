<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'slug',
        'image',
        'content',
        'type',
        'sort',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'sort' => 'integer'
    ];

    /**
     * 根据类型获取文章
     */
    public static function getByType($type = 'article', $limit = null)
    {
        $query = self::where('status', 1)
            ->where('type', $type)
            ->orderBy('sort', 'desc')
            ->orderBy('id', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * 根据slug获取文章
     */
    public static function getBySlug($slug)
    {
        return self::where('slug', $slug)
            ->where('status', 1)
            ->first();
    }
}






