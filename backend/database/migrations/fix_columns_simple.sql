-- 简单版本 - 修复缺失的数据库列
-- 如果列已存在会报错，可以忽略

-- 1. 订单表添加 shipped_at 列
ALTER TABLE `orders` ADD COLUMN `shipped_at` TIMESTAMP NULL;

-- 2. 地址表添加 email, city, postal_code 列
ALTER TABLE `addresses` ADD COLUMN `email` VARCHAR(255) NULL AFTER `phone`;
ALTER TABLE `addresses` ADD COLUMN `city` VARCHAR(100) NULL AFTER `address`;
ALTER TABLE `addresses` ADD COLUMN `postal_code` VARCHAR(20) NULL AFTER `city`;

-- 3. 订阅表添加 cancelled_at 列
ALTER TABLE `subscriptions` ADD COLUMN `cancelled_at` TIMESTAMP NULL;




