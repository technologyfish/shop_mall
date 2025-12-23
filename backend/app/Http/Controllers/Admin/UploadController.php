<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    /**
     * 上传图片
     */
    public function upload(Request $request)
    {
        try {
            // 检查文件是否存在
            if (!$request->hasFile('file')) {
                return response()->json([
                    'code' => 400,
                    'message' => 'No file uploaded'
                ], 400);
            }

            $file = $request->file('file');
            
            // 检查文件是否有效
            if (!$file->isValid()) {
                return response()->json([
                    'code' => 400,
                    'message' => 'Invalid file'
                ], 400);
            }
            
            // 生成文件名
            $extension = $file->getClientOriginalExtension();
            $filename = date('YmdHis') . '_' . uniqid() . '.' . $extension;
            
            // 按日期创建目录
            $dateFolder = date('Y-m-d');
            $uploadPath = 'uploads/images/' . $dateFolder;
            
            // 确保目录存在
            $fullPath = base_path('public/' . $uploadPath);
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0777, true);
            }
            
            // 移动文件
            $file->move($fullPath, $filename);
            
            // 返回访问URL
            $url = '/' . $uploadPath . '/' . $filename;
            
            return response()->json([
                'code' => 200,
                'message' => 'Upload successful',
                'data' => [
                    'url' => $url,
                    'filename' => $filename,
                    'path' => $uploadPath . '/' . $filename
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'Upload failed: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
