-- ========================================
-- 分步执行数据库迁移（更安全）
-- 请按顺序一步一步执行
-- ========================================

-- ========================================
-- 步骤1：为订阅计划表添加字段
-- ========================================
-- 检查字段是否存在，如果不存在则添加
SET @dbname = DATABASE();
SET @tablename = 'subscription_plans';
SET @columnname = 'image';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename)
   AND (table_schema = @dbname)
   AND (column_name = @columnname)) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `image` VARCHAR(255) NULL COMMENT \'计划图片\' AFTER `description`;')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'monthly_delivery_date';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename)
   AND (table_schema = @dbname)
   AND (column_name = @columnname)) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `monthly_delivery_date` DATE NULL COMMENT \'每月发货日期\' AFTER `description`;')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ========================================
-- 步骤2：为订单表添加字段
-- ========================================
SET @tablename = 'orders';
SET @columnname = 'shipping_fee';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename)
   AND (table_schema = @dbname)
   AND (column_name = @columnname)) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `shipping_fee` DECIMAL(10,2) DEFAULT 0.00 COMMENT \'运费\' AFTER `pay_amount`;')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'discount_amount';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename)
   AND (table_schema = @dbname)
   AND (column_name = @columnname)) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `discount_amount` DECIMAL(10,2) DEFAULT 0.00 COMMENT \'折扣金额\' AFTER `pay_amount`;')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'promotion_id';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename)
   AND (table_schema = @dbname)
   AND (column_name = @columnname)) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `promotion_id` INT UNSIGNED NULL COMMENT \'促销活动ID\' AFTER `pay_amount`;')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ========================================
-- 步骤3：创建运费设置表
-- ========================================
CREATE TABLE IF NOT EXISTS `shipping_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `shipping_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '基础运费',
  `free_shipping_threshold` DECIMAL(10,2) DEFAULT NULL COMMENT '免运费门槛金额',
  `status` TINYINT(1) DEFAULT 1 COMMENT '是否启用',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入默认数据（如果不存在）
INSERT INTO `shipping_settings` (`shipping_fee`, `free_shipping_threshold`, `status`, `created_at`, `updated_at`)
SELECT 5.99, 50.00, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `shipping_settings` LIMIT 1);

-- ========================================
-- 步骤4：创建促销活动表
-- ========================================
CREATE TABLE IF NOT EXISTS `promotions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL COMMENT '活动名称',
  `discount_type` VARCHAR(50) NOT NULL COMMENT '折扣类型',
  `discount_value` DECIMAL(10,2) NOT NULL COMMENT '折扣值',
  `require_mail_transfer` TINYINT(1) DEFAULT 0 COMMENT '是否需要提交MailTransfer',
  `target_users` VARCHAR(50) DEFAULT 'all' COMMENT '目标用户',
  `popup_enabled` TINYINT(1) DEFAULT 1 COMMENT '是否弹窗',
  `status` TINYINT(1) DEFAULT 1 COMMENT '是否启用',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入默认促销活动
INSERT INTO `promotions` (`name`, `discount_type`, `discount_value`, `require_mail_transfer`, `target_users`, `popup_enabled`, `status`, `created_at`, `updated_at`)
SELECT '首单折扣', '10%OFF', 10.00, 1, 'all', 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `promotions` WHERE `name` = '首单折扣');

-- ========================================
-- 步骤5：创建MailTransfer表单提交表
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
-- 步骤6：创建Contact表单提交表
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
-- 完成！验证迁移结果
-- ========================================
SELECT '迁移完成！' AS status;

-- 验证表是否创建成功
SELECT COUNT(*) as shipping_settings_exists FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'shipping_settings';

SELECT COUNT(*) as promotions_exists FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'promotions';

SELECT COUNT(*) as mail_transfer_exists FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'mail_transfer_submissions';

SELECT COUNT(*) as contact_submissions_exists FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'contact_submissions';



