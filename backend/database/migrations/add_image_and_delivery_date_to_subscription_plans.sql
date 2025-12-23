-- 为订阅计划添加图片和每月发货日期字段

ALTER TABLE `subscription_plans`
ADD COLUMN `image` VARCHAR(255) NULL COMMENT '计划图片' AFTER `description`,
ADD COLUMN `monthly_delivery_date` DATE NULL COMMENT '每月发货日期' AFTER `subscription_period`;

-- 为订单表添加运费字段（功能3需要）
ALTER TABLE `orders`
ADD COLUMN `shipping_fee` DECIMAL(10,2) DEFAULT 0.00 COMMENT '运费' AFTER `pay_amount`;



