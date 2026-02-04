-- 添加 template_id 字段到 email_tasks 表
-- 用于存储 Postmark 邮件模板 ID

ALTER TABLE `email_tasks` ADD COLUMN `template_id` VARCHAR(50) NULL AFTER `content`;

-- 更新说明:
-- template_id: Postmark 邮件模板ID，如 43078204
-- 使用模板发送邮件时，会自动调用 Postmark API 的模板发送功能





