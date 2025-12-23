-- 从商品表移除运费相关字段（如果存在）
-- 改用统一的 shipping_settings 表管理运费

-- 检查并删除 shipping_fee 字段
SET @dbname = DATABASE();
SET @tablename = 'products';
SET @columnname = 'shipping_fee';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename)
   AND (table_schema = @dbname)
   AND (column_name = @columnname)) > 0,
  CONCAT('ALTER TABLE ', @tablename, ' DROP COLUMN `', @columnname, '`;'),
  'SELECT 1'
));
PREPARE alterIfExists FROM @preparedStatement;
EXECUTE alterIfExists;
DEALLOCATE PREPARE alterIfExists;

-- 检查并删除 free_shipping_threshold 字段
SET @columnname = 'free_shipping_threshold';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename)
   AND (table_schema = @dbname)
   AND (column_name = @columnname)) > 0,
  CONCAT('ALTER TABLE ', @tablename, ' DROP COLUMN `', @columnname, '`;'),
  'SELECT 1'
));
PREPARE alterIfExists FROM @preparedStatement;
EXECUTE alterIfExists;
DEALLOCATE PREPARE alterIfExists;






