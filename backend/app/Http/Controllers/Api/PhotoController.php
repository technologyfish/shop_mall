<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Illuminate\Http\Request;

class PhotoController extends Controller
{
    /**
     * 获取照片列表（前端）
     */
    public function index(Request $request)
    {
        $query = Photo::where('status', 1);

        // 分页或全部
        if ($request->has('per_page')) {
            $photos = $query->orderBy('sort', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 12));
            return $this->paginate($photos);
        } else {
            $photos = $query->orderBy('sort', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
            return $this->success($photos);
        }
    }

    /**
     * 获取首页展示的照片（限制数量）
     */
    public function featured(Request $request)
    {
        $limit = $request->get('limit', 6);
        
        $photos = Photo::where('status', 1)
            ->orderBy('sort', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $this->success($photos);
    }

    /**
     * 获取单个照片详情
     */
    public function show($id)
    {
        $photo = Photo::where('status', 1)->find($id);

        if (!$photo) {
            return $this->error('Photo not found', 404);
        }

        return $this->success($photo);
    }
}




