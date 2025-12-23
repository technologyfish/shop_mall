-- 首单优惠功能数据库迁移
-- 执行时间: 2025-12-19

-- 1. 为 mail_transfer_submissions 表添加 off_code 字段
ALTER TABLE `mail_transfer_submissions` 
ADD COLUMN IF NOT EXISTS `off_code` VARCHAR(50) NULL COMMENT '优惠码，如 OFF10XXXX' AFTER `email`;

-- 添加索引
-- ALTER TABLE `mail_transfer_submissions` ADD INDEX IF NOT EXISTS `idx_off_code` (`off_code`);

-- 2. 为 users 表添加首单优惠相关字段
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `first_order_discount` TINYINT(1) DEFAULT 0 COMMENT '首单优惠状态 0=无/已使用 1=激活' AFTER `is_subscriber`,
ADD COLUMN IF NOT EXISTS `off_code` VARCHAR(50) NULL COMMENT '用户使用的优惠码' AFTER `first_order_discount`;

-- 3. 为 orders 表添加折扣相关字段（如果不存在）
ALTER TABLE `orders`
ADD COLUMN IF NOT EXISTS `discount_amount` DECIMAL(10,2) DEFAULT 0 COMMENT '折扣金额' AFTER `pay_amount`,
ADD COLUMN IF NOT EXISTS `promotion_id` INT UNSIGNED NULL COMMENT '使用的促销活动ID' AFTER `discount_amount`;

-- 验证
SELECT 'mail_transfer_submissions table updated with off_code' AS status;
SELECT 'users table updated with first_order_discount and off_code' AS status;
SELECT 'orders table updated with discount_amount and promotion_id' AS status;



