<?php

namespace App\Services;

class MailService
{
    /**
     * 发送验证码邮件
     * 
     * @param string $email 收件人邮箱
     * @param string $code 验证码
     * @return bool
     */
    public static function sendVerificationCode($email, $code)
    {
        $mode = env('MAIL_MODE', 'log'); // log=测试模式 smtp=正式模式

        if ($mode === 'log') {
            // 测试环境：记录到日志
            return self::logVerificationCode($email, $code);
        } else {
            // 正式环境：发送真实邮件
            return self::sendSMTPMail($email, $code);
        }
    }

    /**
     * 发送 HTML 邮件
     * 
     * @param string $email 收件人邮箱
     * @param string $subject 邮件主题
     * @param string $content 邮件内容
     * @return bool
     */
    public static function sendHtmlMail($email, $subject, $content)
    {
        $mode = env('MAIL_MODE', 'log');

        if ($mode === 'log') {
            \Log::info('Email sent (TEST MODE)', [
                'to' => $email,
                'subject' => $subject,
                'content' => $content
            ]);
            return true;
        }

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // SMTP 配置
            $mail->isSMTP();
            $mail->Host = env('MAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = env('MAIL_USERNAME');
            $mail->Password = env('MAIL_PASSWORD');
            
            $encryption = env('MAIL_ENCRYPTION', 'tls');
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = env('MAIL_PORT', 465);
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = env('MAIL_PORT', 587);
            }

            $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME', 'The Chilli Trail'));
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $content;
            $mail->AltBody = strip_tags($content);

            $mail->send();
            return true;

        } catch (\Exception $e) {
            \Log::error('Email sending failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 测试环境：记录验证码到日志
     */
    private static function logVerificationCode($email, $code)
    {
        $logFile = storage_path('logs/verification-codes.log');
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $message = sprintf(
            "[%s] Email: %s | Code: %s\n",
            date('Y-m-d H:i:s'),
            $email,
            $code
        );

        file_put_contents($logFile, $message, FILE_APPEND);

        \Log::info('Verification code sent (TEST MODE)', [
            'email' => $email,
            'code' => $code
        ]);

        return true;
    }

    /**
     * 正式环境：通过SMTP发送邮件
     */
    private static function sendSMTPMail($email, $code)
    {
        try {
            // 使用 PHPMailer
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // SMTP 配置
            $mail->isSMTP();
            $mail->Host = env('MAIL_HOST', 'smtp.qq.com');
            $mail->SMTPAuth = true;
            $mail->Username = env('MAIL_USERNAME');
            $mail->Password = env('MAIL_PASSWORD');
            
            // 加密方式
            $encryption = env('MAIL_ENCRYPTION', 'tls');
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = env('MAIL_PORT', 465);
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = env('MAIL_PORT', 587);
            }

            // 发件人
            $mail->setFrom(
                env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                env('MAIL_FROM_NAME', 'The Chilli Trail')
            );

            // 收件人
            $mail->addAddress($email);

            // 邮件内容
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Password Reset Verification Code';
            $mail->Body = self::getEmailTemplate($code);
            $mail->AltBody = "Your verification code is: {$code}. This code will expire in 15 minutes.";

            // 发送
            $mail->send();

            \Log::info('Verification code sent via SMTP', [
                'email' => $email,
                'smtp_host' => $mail->Host,
                'smtp_port' => $mail->Port
            ]);

            return true;

        } catch (\PHPMailer\PHPMailer\Exception $e) {
            \Log::error('PHPMailer Exception', [
                'email' => $email,
                'error' => $e->getMessage(),
                'error_info' => $e->errorMessage()
            ]);
            return false;
        } catch (\Exception $e) {
            \Log::error('Email sending exception', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 邮件模板
     */
    private static function getEmailTemplate($code)
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password Reset</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="padding: 40px 50px; text-align: center;">
                            <h1 style="color: #333; font-size: 28px; margin: 0 0 20px 0;">Password Reset</h1>
                            <p style="color: #666; font-size: 16px; line-height: 1.6; margin: 0 0 30px 0;">
                                You have requested to reset your password. Please use the verification code below:
                            </p>
                            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 30px 0;">
                                <div style="font-size: 36px; font-weight: bold; color: #4a90e2; letter-spacing: 8px;">
                                    {$code}
                                </div>
                            </div>
                            <p style="color: #999; font-size: 14px; line-height: 1.6; margin: 30px 0 0 0;">
                                This code will expire in 15 minutes.<br>
                                If you didn't request this, please ignore this email.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 50px; background-color: #f8f9fa; border-top: 1px solid #eee; text-align: center;">
                            <p style="color: #999; font-size: 12px; margin: 0;">
                                © 2025 The Chilli Trail. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}

