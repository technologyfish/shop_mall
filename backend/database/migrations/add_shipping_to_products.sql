-- 为products表添加运费相关字段

ALTER TABLE `products` 
ADD COLUMN `shipping_fee` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT '基础运费' AFTER `status`,
ADD COLUMN `free_shipping_threshold` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT '免运费门槛' AFTER `shipping_fee`;



