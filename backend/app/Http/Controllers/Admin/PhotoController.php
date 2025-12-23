<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Illuminate\Http\Request;

class PhotoController extends Controller
{
    /**
     * 获取照片列表
     */
    public function index(Request $request)
    {
        $query = Photo::query();

        // 搜索
        if ($request->has('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // 状态筛选
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $photos = $query->orderBy('sort', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginate($photos);
    }

    /**
     * 获取单个照片
     */
    public function show($id)
    {
        $photo = Photo::find($id);

        if (!$photo) {
            return $this->error('Photo not found', 404);
        }

        return $this->success($photo);
    }

    /**
     * 创建照片
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'image' => 'required|string|max:500',
            'description' => 'nullable|string',
            'sort' => 'nullable|integer',
            'status' => 'nullable|boolean',
        ]);

        $photo = Photo::create($request->all());

        return $this->success($photo, 'Photo created successfully', 201);
    }

    /**
     * 更新照片
     */
    public function update(Request $request, $id)
    {
        $photo = Photo::find($id);

        if (!$photo) {
            return $this->error('Photo not found', 404);
        }

        $this->validate($request, [
            'name' => 'sometimes|required|string|max:255',
            'image' => 'sometimes|required|string|max:500',
            'description' => 'nullable|string',
            'sort' => 'nullable|integer',
            'status' => 'nullable|boolean',
        ]);

        $photo->update($request->all());

        return $this->success($photo, 'Photo updated successfully');
    }

    /**
     * 删除照片
     */
    public function destroy($id)
    {
        $photo = Photo::find($id);

        if (!$photo) {
            return $this->error('Photo not found', 404);
        }

        $photo->delete();

        return $this->success(null, 'Photo deleted successfully');
    }
}




