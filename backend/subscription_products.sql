-- 添加订阅相关字段到products表
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS is_subscription TINYINT(1) DEFAULT 0 COMMENT '是否订阅商品：0=普通商品 1=订阅商品' AFTER status,
ADD COLUMN IF NOT EXISTS tags JSON NULL COMMENT '商品标签：["featured", "new"]' AFTER is_subscription,
ADD COLUMN IF NOT EXISTS stripe_price_id VARCHAR(255) NULL COMMENT 'Stripe订阅价格ID' AFTER tags;

-- 添加索引
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_is_subscription (is_subscription);

-- 从is_featured和is_new生成tags（如果存在这些字段）
UPDATE products 
SET tags = JSON_ARRAY(
  CASE WHEN is_featured = 1 THEN 'featured' ELSE NULL END,
  CASE WHEN is_new = 1 THEN 'new' ELSE NULL END
)
WHERE (is_featured = 1 OR is_new = 1) AND tags IS NULL;

-- 查看结果
SELECT id, name, price, is_subscription, tags, stripe_price_id FROM products;

