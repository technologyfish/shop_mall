-- ========================================
-- 兼容所有MySQL版本的迁移脚本
-- 请逐个执行每个步骤
-- ========================================

-- ========================================
-- 步骤1：为订阅计划表添加image字段
-- ========================================
ALTER TABLE `subscription_plans`
ADD COLUMN `image` VARCHAR(255) NULL COMMENT '计划图片' AFTER `description`;

-- 如果上面报错"字段已存在"，忽略错误继续执行下一步

-- ========================================
-- 步骤2：为订阅计划表添加monthly_delivery_date字段
-- ========================================
ALTER TABLE `subscription_plans`
ADD COLUMN `monthly_delivery_date` DATE NULL COMMENT '每月发货日期' AFTER `description`;

-- 如果上面报错"字段已存在"，忽略错误继续执行下一步

-- ========================================
-- 步骤3：为订单表添加shipping_fee字段
-- ========================================
ALTER TABLE `orders`
ADD COLUMN `shipping_fee` DECIMAL(10,2) DEFAULT 0.00 COMMENT '运费' AFTER `pay_amount`;

-- ========================================
-- 步骤4：为订单表添加discount_amount字段
-- ========================================
ALTER TABLE `orders`
ADD COLUMN `discount_amount` DECIMAL(10,2) DEFAULT 0.00 COMMENT '折扣金额' AFTER `shipping_fee`;

-- ========================================
-- 步骤5：为订单表添加promotion_id字段
-- ========================================
ALTER TABLE `orders`
ADD COLUMN `promotion_id` INT UNSIGNED NULL COMMENT '促销活动ID' AFTER `discount_amount`;

-- ========================================
-- 步骤6：创建运费设置表
-- ========================================
CREATE TABLE IF NOT EXISTS `shipping_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `shipping_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '基础运费',
  `free_shipping_threshold` DECIMAL(10,2) DEFAULT NULL COMMENT '免运费门槛',
  `status` TINYINT(1) DEFAULT 1 COMMENT '是否启用',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 步骤7：插入默认运费设置
-- ========================================
INSERT INTO `shipping_settings` (`shipping_fee`, `free_shipping_threshold`, `status`, `created_at`, `updated_at`)
VALUES (5.99, 50.00, 1, NOW(), NOW());

-- 如果上面报错"已有数据"，忽略错误继续执行下一步

-- ========================================
-- 步骤8：创建促销活动表
-- ========================================
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

-- ========================================
-- 步骤9：插入默认促销活动
-- ========================================
INSERT INTO `promotions` (`name`, `discount_type`, `discount_value`, `require_mail_transfer`, `target_users`, `popup_enabled`, `status`, `created_at`, `updated_at`)
VALUES ('首单折扣', '10%OFF', 10.00, 1, 'all', 1, 1, NOW(), NOW());

-- 如果上面报错"已有数据"，忽略错误继续执行下一步

-- ========================================
-- 步骤10：创建MailTransfer表单提交表
-- ========================================
CREATE TABLE IF NOT EXISTS `mail_transfer_submissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nickname` VARCHAR(100) NOT NULL COMMENT '昵称',
  `email` VARCHAR(255) NOT NULL COMMENT '邮箱',
  `user_id` INT UNSIGNED NULL COMMENT '用户ID',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 步骤11：创建Contact表单提交表
-- ========================================
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

-- ========================================
-- 完成！
-- ========================================
SELECT '✅ 数据库迁移完成！' AS result;



