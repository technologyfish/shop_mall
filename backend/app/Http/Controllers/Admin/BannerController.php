<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    public function index(Request $request)
    {
        try {
            $position = $request->input('position');
            $query = Banner::query();
            
            if ($position) {
                $query->where('position', $position);
            }
            
            $banners = $query->orderBy('sort', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($request->input('limit', 20));

            return response()->json(['code' => 0, 'message' => 'success', 'data' => $banners]);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $banner = Banner::findOrFail($id);
            return response()->json(['code' => 0, 'message' => 'success', 'data' => $banner]);
        } catch (\Exception $e) {
            return response()->json(['code' => 404, 'message' => 'Banner not found'], 404);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'image' => 'required|string|max:500',
            'link' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:50',
            'sort' => 'nullable|integer',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['code' => 422, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $banner = Banner::create($request->all());
            return response()->json(['code' => 0, 'message' => 'Banner created successfully', 'data' => $banner], 201);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $banner = Banner::findOrFail($id);
            
            // 确保 status 是整数
            $data = $request->all();
            if (isset($data['status'])) {
                $data['status'] = (int)$data['status'];
            }
            
            $banner->update($data);
            
            // 重新获取确保数据一致
            $banner->refresh();
            
            return response()->json(['code' => 0, 'message' => 'Banner updated successfully', 'data' => $banner]);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            Banner::findOrFail($id)->delete();
            return response()->json(['code' => 0, 'message' => 'Banner deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }
}






