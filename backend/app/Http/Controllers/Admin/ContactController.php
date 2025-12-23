<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactInfo;
use App\Models\ContactForm;
use App\Models\ContactSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * 联系信息列表
     */
    public function getContactInfo()
    {
        $contactInfo = ContactInfo::orderBy('sort')->get();
        return $this->success($contactInfo);
    }

    /**
     * 更新联系信息
     */
    public function updateContactInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.key' => 'required|string',
            'items.*.value' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        try {
            foreach ($request->items as $item) {
                ContactInfo::updateOrCreate(
                    ['key' => $item['key']],
                    [
                        'value' => $item['value'],
                        'label' => $item['label'] ?? '',
                        'type' => $item['type'] ?? 'text',
                        'sort' => $item['sort'] ?? 0
                    ]
                );
            }

            return $this->success(null, 'Contact info updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Contact表单提交列表
     */
    public function getForms(Request $request)
    {
        $query = ContactSubmission::query();

        // 状态筛选
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // 搜索
        if ($request->has('keyword') && $request->keyword) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%")
                  ->orWhere('subject', 'like', "%{$keyword}%");
            });
        }

        $forms = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginate($forms);
    }

    /**
     * Contact表单详情
     */
    public function getForm($id)
    {
        $form = ContactSubmission::find($id);
        
        if (!$form) {
            return $this->error('Form not found', 404);
        }
        
        return $this->success($form);
    }

    /**
     * 更新Contact表单状态
     */
    public function updateFormStatus($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $form = ContactSubmission::find($id);
        
        if (!$form) {
            return $this->error('Form not found', 404);
        }

        $form->status = $request->status;
        $form->save();

        return $this->success($form, 'Status updated successfully');
    }

    /**
     * 删除Contact表单
     */
    public function deleteForm($id)
    {
        $form = ContactSubmission::find($id);
        
        if (!$form) {
            return $this->error('Form not found', 404);
        }

        $form->delete();
        return $this->success(null, 'Form deleted successfully');
    }
}



