<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    protected $fillable = [
        'email', 'code', 'expires_at', 'is_used'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'integer',
    ];

    /**
     * 生成6位数字验证码
     */
    public static function generateCode()
    {
        return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * 验证码是否有效
     */
    public function isValid()
    {
        return !$this->is_used && $this->expires_at->isFuture();
    }

    /**
     * 标记为已使用
     */
    public function markAsUsed()
    {
        $this->is_used = 1;
        $this->save();
    }
}




