<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTask extends Model
{
    const TYPE_WELCOME = 'register'; // 注册欢迎邮件
    const TYPE_ORDER_CONFIRMATION = 'order'; // 订单确认邮件
    const TYPE_CAMPAIGN = 'holiday'; // 营销活动邮件/节假日邮件

    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    protected $fillable = [
        'name', 'type', 'subject', 'content', 'status', 
        'variables', 'schedule_time'
    ];

    protected $casts = [
        'status' => 'integer',
        'schedule_time' => 'datetime',
        'variables' => 'array'
    ];
}

