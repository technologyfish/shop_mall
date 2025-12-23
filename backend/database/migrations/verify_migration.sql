-- 验证数据库迁移是否成功

-- 检查subscription_plans表的字段
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'subscription_plans'
  AND COLUMN_NAME IN ('image', 'monthly_delivery_date')
ORDER BY ORDINAL_POSITION;

-- 检查orders表的新字段
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'orders'
  AND COLUMN_NAME IN ('shipping_fee', 'discount_amount', 'promotion_id')
ORDER BY ORDINAL_POSITION;

-- 检查新创建的表
SELECT 
    TABLE_NAME,
    TABLE_COMMENT
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('shipping_settings', 'promotions', 'mail_transfer_submissions', 'contact_submissions');

-- 检查运费设置数据
SELECT * FROM shipping_settings;

-- 检查促销活动数据
SELECT * FROM promotions;



