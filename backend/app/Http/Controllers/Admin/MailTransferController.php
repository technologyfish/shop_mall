<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailTransferSubmission;
use Illuminate\Http\Request;

class MailTransferController extends Controller
{
    /**
     * MailTransfer表单列表
     */
    public function getForms(Request $request)
    {
        $query = MailTransferSubmission::query();

        // 搜索
        if ($request->has('keyword') && $request->keyword) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('nickname', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        $forms = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginate($forms);
    }

    /**
     * MailTransfer表单详情
     */
    public function getForm($id)
    {
        $form = MailTransferSubmission::find($id);
        
        if (!$form) {
            return $this->error('Form not found', 404);
        }
        
        return $this->success($form);
    }

    /**
     * 删除MailTransfer表单
     */
    public function deleteForm($id)
    {
        $form = MailTransferSubmission::find($id);
        
        if (!$form) {
            return $this->error('Form not found', 404);
        }

        $form->delete();
        return $this->success(null, 'Form deleted successfully');
    }
}



