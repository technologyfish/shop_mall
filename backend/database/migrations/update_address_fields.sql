-- 修改地址表：添加email, city, postal_code字段
ALTER TABLE `addresses` 
ADD COLUMN `email` VARCHAR(255) NULL AFTER `phone`,
ADD COLUMN `city` VARCHAR(100) NULL AFTER `address`,
ADD COLUMN `postal_code` VARCHAR(20) NULL AFTER `city`;

-- 如果字段已存在，可以使用以下语句更新（可选）
-- UPDATE addresses SET email = '', city = '', postal_code = '' WHERE email IS NULL;






