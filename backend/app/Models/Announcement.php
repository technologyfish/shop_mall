<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $table = 'announcements';

    protected $fillable = [
        'content',
        'link',
        'sort',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
        'sort' => 'integer',
    ];

    /**
     * 获取启用的公告
     */
    public static function getActive()
    {
        return self::where('status', 1)
            ->orderBy('sort', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
