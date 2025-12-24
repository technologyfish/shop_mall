<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactInfo;
use App\Models\ContactForm;
use App\Models\ContactSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * 获取联系信息
     */
    public function getContactInfo()
    {
        $contactInfo = ContactInfo::orderBy('sort')->get();
        
        // 转换为键值对格式
        $data = [];
        foreach ($contactInfo as $item) {
            $data[$item->key] = [
                'value' => $item->value,
                'label' => $item->label,
                'type' => $item->type
            ];
        }
        
        return $this->success($data);
    }

    /**
     * 提交Contact表单
     */
    public function submitForm(Request $request)
    {
        // 获取所有输入数据（支持 JSON 和表单数据）
        $data = $request->all();
        
        \Log::info('Contact form submission', ['data' => $data]);
        
        $validator = Validator::make($data, [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'phone' => 'nullable|string|max:50',
            'comment' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        try {
            $form = ContactSubmission::create([
                'name' => $data['name'] ?? '',
                'email' => $data['email'] ?? '',
                'phone' => $data['phone'] ?? '',
                'message' => $data['comment'] ?? '',
                'status' => 0,
            ]);

            \Log::info('Contact form saved', ['form' => $form->toArray()]);

            return $this->success($form, 'Form submitted successfully', 201);
        } catch (\Exception $e) {
            \Log::error('Contact form error', ['error' => $e->getMessage()]);
            return $this->error($e->getMessage(), 400);
        }
    }
}



