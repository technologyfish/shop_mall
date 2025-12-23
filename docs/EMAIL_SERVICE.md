# 邮件服务 技术文档

## 一、概述

本项目邮件功能用于：
- 用户注册验证
- 忘记密码重置
- 订单确认通知
- 订阅支付成功通知
- 发货通知

---

## 二、配置方式

### 2.1 环境变量配置

```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="Your Shop"
```

---

## 三、推荐邮件服务商

### 3.1 SendGrid（推荐）

**优点**：免费额度大（100封/天），稳定可靠

**配置**：
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.你的API_Key
MAIL_ENCRYPTION=tls
```

**获取 API Key**：
1. 注册 [SendGrid](https://sendgrid.com)
2. 进入 **Settings** -> **API Keys**
3. 创建 API Key，选择 **Full Access**

### 3.2 Mailgun

**配置**：
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@your-domain.mailgun.org
MAIL_PASSWORD=your-mailgun-password
MAIL_ENCRYPTION=tls
```

### 3.3 AWS SES

**优点**：便宜，适合大量发送

**配置**：
```env
MAIL_DRIVER=smtp
MAIL_HOST=email-smtp.us-east-1.amazonaws.com
MAIL_PORT=587
MAIL_USERNAME=AKIA...
MAIL_PASSWORD=your-ses-password
MAIL_ENCRYPTION=tls
```

### 3.4 阿里云邮件推送

**配置**：
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtpdm.aliyun.com
MAIL_PORT=465
MAIL_USERNAME=your-account@your-domain.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=ssl
```

### 3.5 Gmail（仅测试用）

**配置**：
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password  # 需要使用应用专用密码
MAIL_ENCRYPTION=tls
```

**注意**：Gmail 需要：
1. 开启两步验证
2. 生成应用专用密码：Google 账户 -> 安全性 -> 应用专用密码

---

## 四、邮件发送代码

### 4.1 发送邮件工具类

**文件**: `app/Services/MailService.php`

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;

class MailService
{
    /**
     * 发送重置密码邮件
     */
    public static function sendPasswordReset($email, $token)
    {
        $resetUrl = env('APP_FRONTEND_URL') . '/reset-password?token=' . $token . '&email=' . urlencode($email);
        
        Mail::send('emails.password-reset', [
            'resetUrl' => $resetUrl,
        ], function ($message) use ($email) {
            $message->to($email)
                    ->subject('Reset Your Password');
        });
    }

    /**
     * 发送订单确认邮件
     */
    public static function sendOrderConfirmation($order)
    {
        Mail::send('emails.order-confirmation', [
            'order' => $order,
        ], function ($message) use ($order) {
            $message->to($order->shipping_email)
                    ->subject('Order Confirmation #' . $order->order_no);
        });
    }

    /**
     * 发送订阅成功邮件
     */
    public static function sendSubscriptionSuccess($subscription)
    {
        $user = $subscription->user;
        
        Mail::send('emails.subscription-success', [
            'subscription' => $subscription,
            'user' => $user,
        ], function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Welcome to The Sauce Club!');
        });
    }
}
```

### 4.2 邮件模板

**文件**: `resources/views/emails/password-reset.blade.php`

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Your Password</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button { 
            display: inline-block; 
            padding: 12px 24px; 
            background: #FF5722; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reset Your Password</h1>
        <p>You requested to reset your password. Click the button below:</p>
        <p>
            <a href="{{ $resetUrl }}" class="button">Reset Password</a>
        </p>
        <p>If you didn't request this, please ignore this email.</p>
        <p>This link will expire in 60 minutes.</p>
    </div>
</body>
</html>
```

---

## 五、邮件触发场景

### 5.1 自动发送

| 场景 | 触发位置 | 邮件内容 |
|------|----------|----------|
| 忘记密码 | `AuthController@forgotPassword` | 重置密码链接 |
| 订单支付成功 | `StripeWebhookController` | 订单确认 |
| 订阅成功 | `StripeWebhookController` | 欢迎加入订阅 |
| 订阅续费成功 | `handleInvoicePaymentSucceeded` | 续费确认 |
| 订阅失败 | `handleInvoicePaymentFailed` | 支付失败提醒 |

### 5.2 手动发送（后台）

- 发货通知
- 营销邮件（通过邮件任务管理）

---

## 六、邮件调试

### 6.1 日志模式（开发环境）

```env
MAIL_DRIVER=log
```

邮件内容会写入 `storage/logs/lumen-*.log`

### 6.2 Mailtrap（测试环境）

[Mailtrap](https://mailtrap.io) 提供免费的邮件测试服务：

```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
```

---

## 七、常见问题

### Q: 邮件发送失败？

1. 检查 SMTP 配置是否正确
2. 检查端口是否被防火墙阻止
3. 查看日志：`storage/logs/lumen-*.log`

### Q: Gmail 发送失败？

1. 确认已开启两步验证
2. 使用应用专用密码，不是登录密码
3. 检查是否被 Google 安全策略阻止

### Q: 邮件进垃圾箱？

1. 配置 SPF、DKIM、DMARC 记录
2. 使用专业邮件服务商（SendGrid/Mailgun）
3. 避免使用免费邮箱作为发件人

### Q: 发送速度慢？

1. 使用队列异步发送：
   ```env
   QUEUE_CONNECTION=database
   ```
2. 运行队列处理：
   ```bash
   php artisan queue:work
   ```

---

## 八、域名邮件配置（推荐）

### 8.1 DNS 记录配置

为了提高邮件送达率，建议配置：

**SPF 记录**（TXT）：
```
v=spf1 include:sendgrid.net ~all
```

**DKIM 记录**：按照邮件服务商的指引配置

**DMARC 记录**（TXT）：
```
v=DMARC1; p=none; rua=mailto:dmarc@your-domain.com
```

### 8.2 发件人域名验证

大多数邮件服务商要求验证发件人域名：
1. SendGrid: **Settings** -> **Sender Authentication**
2. Mailgun: **Domains** -> **Add New Domain**
3. AWS SES: **Verified identities**



