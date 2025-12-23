<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTask;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmailTaskController extends Controller
{
    /**
     * 获取邮件任务列表
     */
    public function index(Request $request)
    {
        $query = EmailTask::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $tasks = $query->orderBy('created_at', 'desc')->get();

        return $this->success($tasks);
    }

    /**
     * 获取邮件任务详情
     */
    public function show($id)
    {
        $task = EmailTask::find($id);

        if (!$task) {
            return $this->error('Email task not found', 404);
        }

        return $this->success($task);
    }

    /**
     * 创建邮件任务
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'type' => 'required|string|in:register,order,holiday',
            'subject' => 'required|string|max:200',
            'content' => 'required|string',
            'status' => 'required|integer|in:0,1',
            'schedule_time' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // 设置默认变量说明
        $variables = [];
        if ($request->type == 'register') {
            $variables = ['{username}' => '用户名称', '{email}' => '用户邮箱'];
        } elseif ($request->type == 'order') {
            $variables = ['{username}' => '用户名称', '{order_no}' => '订单号', '{amount}' => '订单金额'];
        } else {
            $variables = ['{username}' => '用户名称'];
        }

        $data = $request->all();
        $data['variables'] = $variables;

        $task = EmailTask::create($data);

        return $this->success($task, 'Email task created successfully', 201);
    }

    /**
     * 更新邮件任务
     */
    public function update(Request $request, $id)
    {
        $task = EmailTask::find($id);

        if (!$task) {
            return $this->error('Email task not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:100',
            'subject' => 'string|max:200',
            'content' => 'string',
            'status' => 'integer|in:0,1',
            'schedule_time' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $task->update($request->all());

        return $this->success($task, 'Email task updated successfully');
    }

    /**
     * 删除邮件任务
     */
    public function destroy($id)
    {
        $task = EmailTask::find($id);

        if (!$task) {
            return $this->error('Email task not found', 404);
        }

        $task->delete();

        return $this->success(null, 'Email task deleted successfully');
    }

    /**
     * 手动发送邮件（用于节假日/新品邮件）
     */
    public function send(Request $request, $id)
    {
        $task = EmailTask::find($id);

        if (!$task) {
            return $this->error('Email task not found', 404);
        }

        // 只有节假日/新品邮件可以手动批量发送
        if ($task->type != EmailTask::TYPE_CAMPAIGN) {
            return $this->error('Only holiday/new product emails can be sent manually', 400);
        }

        // 异步发送（这里简化为同步发送，实际建议使用队列）
        // 获取所有订阅用户（这里假设发送给所有用户）
        $users = User::where('status', 1)->get();
        $count = 0;

        foreach ($users as $user) {
            $subject = str_replace('{username}', $user->username, $task->subject);
            $content = str_replace('{username}', $user->username, $task->content);
            
            // 发送邮件
            MailService::sendHtmlMail($user->email, $subject, $content);
            $count++;
        }

        return $this->success(['count' => $count], "Email sent to {$count} users");
    }
}




