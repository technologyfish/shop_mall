-- 创建运费设置表
CREATE TABLE IF NOT EXISTS `shipping_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `shipping_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '基础运费',
  `free_shipping_threshold` DECIMAL(10,2) DEFAULT NULL COMMENT '免运费门槛金额',
  `status` TINYINT(1) DEFAULT 1 COMMENT '是否启用 1=启用 0=禁用',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='运费设置表';

-- 插入默认运费设置
INSERT INTO `shipping_settings` (`shipping_fee`, `free_shipping_threshold`, `status`, `created_at`, `updated_at`)
VALUES (5.99, 50.00, 1, NOW(), NOW());

-- 为订单表添加折扣字段
ALTER TABLE `orders`
ADD COLUMN `discount_amount` DECIMAL(10,2) DEFAULT 0.00 COMMENT '折扣金额' AFTER `shipping_fee`,
ADD COLUMN `promotion_id` INT UNSIGNED NULL COMMENT '促销活动ID' AFTER `discount_amount`;

-- 创建促销活动表
CREATE TABLE IF NOT EXISTS `promotions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL COMMENT '活动名称',
  `discount_type` VARCHAR(50) NOT NULL COMMENT '折扣类型 10%OFF等',
  `discount_value` DECIMAL(10,2) NOT NULL COMMENT '折扣值',
  `require_mail_transfer` TINYINT(1) DEFAULT 0 COMMENT '是否需要提交MailTransfer表单',
  `target_users` VARCHAR(50) DEFAULT 'all' COMMENT '目标用户 registered/unregistered/all',
  `popup_enabled` TINYINT(1) DEFAULT 1 COMMENT '是否弹窗展示',
  `status` TINYINT(1) DEFAULT 1 COMMENT '是否启用',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='促销活动表';

-- 插入默认首单折扣活动
INSERT INTO `promotions` (`name`, `discount_type`, `discount_value`, `require_mail_transfer`, `target_users`, `popup_enabled`, `status`, `created_at`, `updated_at`)
VALUES ('首单折扣', '10%OFF', 10.00, 1, 'all', 1, 1, NOW(), NOW());

-- 创建MailTransfer表单提交记录表
CREATE TABLE IF NOT EXISTS `mail_transfer_submissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nickname` VARCHAR(100) NOT NULL COMMENT '昵称',
  `email` VARCHAR(255) NOT NULL COMMENT '邮箱',
  `user_id` INT UNSIGNED NULL COMMENT '关联用户ID（如果已注册）',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_email` (`email`),
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='MailTransfer表单提交记录';

-- 创建Contact表单提交记录表（从contacts分离）
CREATE TABLE IF NOT EXISTS `contact_submissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL COMMENT '姓名',
  `email` VARCHAR(255) NOT NULL COMMENT '邮箱',
  `phone` VARCHAR(50) NULL COMMENT '电话',
  `message` TEXT NOT NULL COMMENT '留言内容',
  `status` TINYINT(1) DEFAULT 0 COMMENT '处理状态 0=未处理 1=已处理',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Contact表单提交记录';

-- 如果原有contacts表有数据，迁移到contact_submissions
-- INSERT INTO contact_submissions (name, email, phone, message, created_at, updated_at)
-- SELECT name, email, phone, message, created_at, updated_at FROM contacts WHERE name IS NOT NULL;

-- 修改原contacts表为联系信息配置表（保留一条记录用于配置）
-- 如果需要保留原表结构，可以重命名：RENAME TABLE contacts TO contact_info;



