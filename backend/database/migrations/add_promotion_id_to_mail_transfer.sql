-- 为mail_transfer_submissions表添加promotion_id字段

ALTER TABLE `mail_transfer_submissions` 
ADD COLUMN `promotion_id` INT UNSIGNED NULL AFTER `user_id`,
ADD INDEX `idx_promotion_id` (`promotion_id`);



