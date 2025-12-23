-- ========================================
-- 修复所有SQL错误 - 完整版
-- ========================================

-- 步骤1：为订阅计划表添加字段
ALTER TABLE `subscription_plans`
ADD COLUMN IF NOT EXISTS `image` VARCHAR(255) NULL COMMENT '计划图片' AFTER `description`;

ALTER TABLE `subscription_plans`
ADD COLUMN IF NOT EXISTS `monthly_delivery_date` DATE NULL COMMENT '每月发货日期' AFTER `description`;

-- 步骤2：为订单表添加字段
ALTER TABLE `orders`
ADD COLUMN IF NOT EXISTS `shipping_fee` DECIMAL(10,2) DEFAULT 0.00 COMMENT '运费' AFTER `pay_amount`;

ALTER TABLE `orders`
ADD COLUMN IF NOT EXISTS `discount_amount` DECIMAL(10,2) DEFAULT 0.00 COMMENT '折扣金额' AFTER `pay_amount`;

ALTER TABLE `orders`
ADD COLUMN IF NOT EXISTS `promotion_id` INT UNSIGNED NULL COMMENT '促销活动ID' AFTER `pay_amount`;

-- 步骤3：创建运费设置表
CREATE TABLE IF NOT EXISTS `shipping_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `shipping_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '基础运费',
  `free_shipping_threshold` DECIMAL(10,2) DEFAULT NULL COMMENT '免运费门槛',
  `status` TINYINT(1) DEFAULT 1 COMMENT '是否启用',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `shipping_settings` (`shipping_fee`, `free_shipping_threshold`, `status`, `created_at`, `updated_at`)
SELECT 5.99, 50.00, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `shipping_settings` LIMIT 1);

-- 步骤4：创建促销活动表（修复DECIMAL定义）
CREATE TABLE IF NOT EXISTS `promotions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL COMMENT '活动名称',
  `discount_type` VARCHAR(50) NOT NULL COMMENT '折扣类型',
  `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '折扣值',
  `require_mail_transfer` TINYINT(1) DEFAULT 0 COMMENT '是否需要MailTransfer',
  `target_users` VARCHAR(50) DEFAULT 'all' COMMENT '目标用户',
  `popup_enabled` TINYINT(1) DEFAULT 1 COMMENT '是否弹窗',
  `status` TINYINT(1) DEFAULT 1 COMMENT '是否启用',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `promotions` (`name`, `discount_type`, `discount_value`, `require_mail_transfer`, `target_users`, `popup_enabled`, `status`, `created_at`, `updated_at`)
SELECT '首单折扣', '10%OFF', 10.00, 1, 'all', 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `promotions` WHERE `name` = '首单折扣');

-- 步骤5：创建MailTransfer表单提交表
CREATE TABLE IF NOT EXISTS `mail_transfer_submissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nickname` VARCHAR(100) NOT NULL COMMENT '昵称',
  `email` VARCHAR(255) NOT NULL COMMENT '邮箱',
  `user_id` INT UNSIGNED NULL COMMENT '用户ID',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 步骤6：创建Contact表单提交表
CREATE TABLE IF NOT EXISTS `contact_submissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL COMMENT '姓名',
  `email` VARCHAR(255) NOT NULL COMMENT '邮箱',
  `phone` VARCHAR(50) NULL COMMENT '电话',
  `message` TEXT NOT NULL COMMENT '留言',
  `status` TINYINT(1) DEFAULT 0 COMMENT '处理状态',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT '✅ 数据库迁移完成！' AS result;



