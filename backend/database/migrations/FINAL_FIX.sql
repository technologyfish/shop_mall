-- ========================================
-- 最终修复脚本 - 仅添加缺失的列
-- 如果报错"列已存在"，忽略并继续
-- ========================================

-- 1. 订单表：确保物流字段存在
ALTER TABLE `orders` ADD COLUMN `shipping_company` VARCHAR(100) NULL COMMENT '物流公司';
ALTER TABLE `orders` ADD COLUMN `shipping_no` VARCHAR(100) NULL COMMENT '物流单号';
ALTER TABLE `orders` ADD COLUMN `shipping_email` VARCHAR(255) NULL COMMENT '收货人邮箱';
ALTER TABLE `orders` ADD COLUMN `shipping_city` VARCHAR(100) NULL COMMENT '收货城市';
ALTER TABLE `orders` ADD COLUMN `shipping_postal_code` VARCHAR(20) NULL COMMENT '邮编';

-- 2. 地址表：确保扩展字段存在
ALTER TABLE `addresses` ADD COLUMN `email` VARCHAR(255) NULL COMMENT '邮箱';
ALTER TABLE `addresses` ADD COLUMN `city` VARCHAR(100) NULL COMMENT '城市';
ALTER TABLE `addresses` ADD COLUMN `postal_code` VARCHAR(20) NULL COMMENT '邮编';

-- 3. 验证结果
SELECT 'orders 表字段:' AS info;
SHOW COLUMNS FROM orders WHERE Field IN ('shipping_company', 'shipping_no', 'shipping_email', 'shipping_city', 'shipping_postal_code');

SELECT 'addresses 表字段:' AS info;
SHOW COLUMNS FROM addresses WHERE Field IN ('email', 'city', 'postal_code');

SELECT '✅ 修复完成！' AS result;




