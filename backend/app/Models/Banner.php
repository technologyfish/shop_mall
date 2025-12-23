<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'link',
        'button_text',
        'position',
        'sort',
        'status'
    ];

    protected $casts = [
        'status' => 'integer',
        'sort' => 'integer'
    ];

    /**
     * 根据位置获取Banner
     */
    public static function getByPosition($position = 'home')
    {
        return self::where('status', 1)
            ->where('position', $position)
            ->orderBy('sort', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }
}






