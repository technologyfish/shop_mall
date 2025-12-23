-- 为订阅计划表添加image字段
ALTER TABLE `subscription_plans`
ADD COLUMN `image` VARCHAR(255) NULL COMMENT '计划图片' AFTER `description`;



