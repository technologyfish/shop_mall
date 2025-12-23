-- Add selected field to carts table for checkbox functionality

ALTER TABLE `carts` ADD COLUMN `selected` TINYINT(1) NOT NULL DEFAULT 1 AFTER `quantity`;

-- Update description
-- This migration adds a 'selected' field to track which cart items are selected for checkout






