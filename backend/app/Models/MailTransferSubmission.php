<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailTransferSubmission extends Model
{
    protected $fillable = [
        'nickname',
        'email',
        'off_code',
        'user_id',
        'promotion_id'
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 检查邮箱是否已提交
     */
    public static function hasSubmitted($email)
    {
        return self::where('email', $email)->exists();
    }
}

