-- 为订阅计划表添加monthly_delivery_date字段
ALTER TABLE `subscription_plans`
ADD COLUMN `monthly_delivery_date` DATE NULL COMMENT '每月发货日期' AFTER `description`;



