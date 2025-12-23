<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    const STATUS_PENDING = 0;
    const STATUS_PROCESSED = 1;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'message',
        'status'
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    /**
     * 标记为已处理
     */
    public function markAsProcessed()
    {
        $this->status = self::STATUS_PROCESSED;
        $this->save();
    }
}



