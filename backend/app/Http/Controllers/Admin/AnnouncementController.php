<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnnouncementController extends Controller
{
    /**
     * 公告列表
     */
    public function index(Request $request)
    {
        $query = Announcement::query();

        // 状态筛选
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // 关键词搜索
        if ($request->has('keyword') && $request->keyword) {
            $query->where('content', 'like', '%' . $request->keyword . '%');
        }

        $announcements = $query->orderBy('sort', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginate($announcements);
    }

    /**
     * 公告详情
     */
    public function show($id)
    {
        $announcement = Announcement::find($id);

        if (!$announcement) {
            return $this->error('Announcement not found', 404);
        }

        return $this->success($announcement);
    }

    /**
     * 创建公告
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:500',
            'link' => 'nullable|string|max:255',
            'sort' => 'nullable|integer',
            'status' => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $announcement = Announcement::create([
            'content' => $request->content,
            'link' => $request->link,
            'sort' => $request->sort ?? 0,
            'status' => $request->status,
        ]);

        return $this->success($announcement, 'Announcement created successfully', 201);
    }

    /**
     * 更新公告
     */
    public function update($id, Request $request)
    {
        $announcement = Announcement::find($id);

        if (!$announcement) {
            return $this->error('Announcement not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:500',
            'link' => 'nullable|string|max:255',
            'sort' => 'nullable|integer',
            'status' => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $announcement->update([
            'content' => $request->content,
            'link' => $request->link ?? null,
            'sort' => $request->sort ?? 0,
            'status' => $request->status,
        ]);

        return $this->success($announcement, 'Announcement updated successfully');
    }

    /**
     * 删除公告
     */
    public function destroy($id)
    {
        $announcement = Announcement::find($id);

        if (!$announcement) {
            return $this->error('Announcement not found', 404);
        }

        $announcement->delete();

        return $this->success(null, 'Announcement deleted successfully');
    }
}
