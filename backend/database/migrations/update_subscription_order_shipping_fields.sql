-- 修改订阅订单表：添加shipping_email, shipping_city, shipping_postal_code字段
ALTER TABLE `subscription_orders` 
ADD COLUMN `shipping_email` VARCHAR(255) NULL AFTER `shipping_phone`,
ADD COLUMN `shipping_city` VARCHAR(100) NULL AFTER `shipping_address`,
ADD COLUMN `shipping_postal_code` VARCHAR(20) NULL AFTER `shipping_city`;

-- 默认值可选
-- UPDATE subscription_orders SET shipping_email = '', shipping_city = '', shipping_postal_code = '' WHERE shipping_email IS NULL;






