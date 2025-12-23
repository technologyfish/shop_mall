<?php
/**
 * 数据库迁移脚本
 * 执行方式：php migrate.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// 加载环境变量
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 数据库配置
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? '';
$username = $_ENV['DB_USERNAME'] ?? '';
$password = $_ENV['DB_PASSWORD'] ?? '';

echo "\n========================================\n";
echo "数据库迁移开始\n";
echo "========================================\n\n";

try {
    // 创建数据库连接
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ 数据库连接成功\n";
    echo "   数据库: {$database}\n\n";
    
    // 定义所有迁移SQL
    $migrations = [
        [
            'name' => '为订阅计划表添加image字段',
            'sql' => "ALTER TABLE `subscription_plans` ADD COLUMN `image` VARCHAR(255) NULL COMMENT '计划图片' AFTER `description`",
            'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '{$database}' AND TABLE_NAME = 'subscription_plans' AND COLUMN_NAME = 'image'"
        ],
        [
            'name' => '为订阅计划表添加monthly_delivery_date字段',
            'sql' => "ALTER TABLE `subscription_plans` ADD COLUMN `monthly_delivery_date` DATE NULL COMMENT '每月发货日期' AFTER `description`",
            'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '{$database}' AND TABLE_NAME = 'subscription_plans' AND COLUMN_NAME = 'monthly_delivery_date'"
        ],
        [
            'name' => '为订单表添加shipping_fee字段',
            'sql' => "ALTER TABLE `orders` ADD COLUMN `shipping_fee` DECIMAL(10,2) DEFAULT 0.00 COMMENT '运费' AFTER `pay_amount`",
            'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '{$database}' AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'shipping_fee'"
        ],
        [
            'name' => '为订单表添加discount_amount字段',
            'sql' => "ALTER TABLE `orders` ADD COLUMN `discount_amount` DECIMAL(10,2) DEFAULT 0.00 COMMENT '折扣金额' AFTER `pay_amount`",
            'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '{$database}' AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'discount_amount'"
        ],
        [
            'name' => '为订单表添加promotion_id字段',
            'sql' => "ALTER TABLE `orders` ADD COLUMN `promotion_id` INT UNSIGNED NULL COMMENT '促销活动ID' AFTER `pay_amount`",
            'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '{$database}' AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'promotion_id'"
        ],
        [
            'name' => '创建运费设置表',
            'sql' => "CREATE TABLE IF NOT EXISTS `shipping_settings` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `shipping_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '基础运费',
                `free_shipping_threshold` DECIMAL(10,2) DEFAULT NULL COMMENT '免运费门槛',
                `status` TINYINT(1) DEFAULT 1 COMMENT '是否启用',
                `created_at` TIMESTAMP NULL DEFAULT NULL,
                `updated_at` TIMESTAMP NULL DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$database}' AND TABLE_NAME = 'shipping_settings'"
        ],
        [
            'name' => '创建促销活动表',
            'sql' => "CREATE TABLE IF NOT EXISTS `promotions` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL COMMENT '活动名称',
                `discount_type` VARCHAR(50) NOT NULL COMMENT '折扣类型',
                `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '折扣值',
                `require_mail_transfer` TINYINT(1) DEFAULT 0 COMMENT '是否需要MailTransfer',
                `target_users` VARCHAR(50) DEFAULT 'all' COMMENT '目标用户',
                `popup_enabled` TINYINT(1) DEFAULT 1 COMMENT '是否弹窗',
                `status` TINYINT(1) DEFAULT 1 COMMENT '是否启用',
                `created_at` TIMESTAMP NULL DEFAULT NULL,
                `updated_at` TIMESTAMP NULL DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$database}' AND TABLE_NAME = 'promotions'"
        ],
        [
            'name' => '创建MailTransfer表单提交表',
            'sql' => "CREATE TABLE IF NOT EXISTS `mail_transfer_submissions` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `nickname` VARCHAR(100) NOT NULL COMMENT '昵称',
                `email` VARCHAR(255) NOT NULL COMMENT '邮箱',
                `user_id` INT UNSIGNED NULL COMMENT '用户ID',
                `created_at` TIMESTAMP NULL DEFAULT NULL,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                INDEX `idx_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$database}' AND TABLE_NAME = 'mail_transfer_submissions'"
        ],
        [
            'name' => '创建Contact表单提交表',
            'sql' => "CREATE TABLE IF NOT EXISTS `contact_submissions` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL COMMENT '姓名',
                `email` VARCHAR(255) NOT NULL COMMENT '邮箱',
                `phone` VARCHAR(50) NULL COMMENT '电话',
                `message` TEXT NOT NULL COMMENT '留言',
                `status` TINYINT(1) DEFAULT 0 COMMENT '处理状态',
                `created_at` TIMESTAMP NULL DEFAULT NULL,
                `updated_at` TIMESTAMP NULL DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'check' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$database}' AND TABLE_NAME = 'contact_submissions'"
        ],
    ];
    
    // 执行迁移
    $successCount = 0;
    $skipCount = 0;
    $errorCount = 0;
    
    foreach ($migrations as $index => $migration) {
        $stepNum = $index + 1;
        echo "步骤 {$stepNum}: {$migration['name']}\n";
        
        try {
            // 检查是否已存在
            $checkResult = $pdo->query($migration['check'])->fetchColumn();
            
            if ($checkResult > 0) {
                echo "   ⏭️  已存在，跳过\n\n";
                $skipCount++;
                continue;
            }
            
            // 执行SQL
            $pdo->exec($migration['sql']);
            echo "   ✅ 执行成功\n\n";
            $successCount++;
            
        } catch (PDOException $e) {
            // 如果是字段/表已存在的错误，跳过
            if (strpos($e->getMessage(), 'Duplicate column') !== false || 
                strpos($e->getMessage(), 'already exists') !== false) {
                echo "   ⏭️  已存在，跳过\n\n";
                $skipCount++;
            } else {
                echo "   ❌ 错误: " . $e->getMessage() . "\n\n";
                $errorCount++;
            }
        }
    }
    
    // 插入默认数据
    echo "步骤 10: 插入默认数据\n";
    try {
        // 检查运费设置是否有数据
        $hasShipping = $pdo->query("SELECT COUNT(*) FROM shipping_settings")->fetchColumn();
        if ($hasShipping == 0) {
            $pdo->exec("INSERT INTO `shipping_settings` (`shipping_fee`, `free_shipping_threshold`, `status`, `created_at`, `updated_at`) VALUES (5.99, 50.00, 1, NOW(), NOW())");
            echo "   ✅ 默认运费设置已插入\n";
        } else {
            echo "   ⏭️  运费设置已存在\n";
        }
        
        // 检查促销活动是否有数据
        $hasPromotion = $pdo->query("SELECT COUNT(*) FROM promotions WHERE name = '首单折扣'")->fetchColumn();
        if ($hasPromotion == 0) {
            $pdo->exec("INSERT INTO `promotions` (`name`, `discount_type`, `discount_value`, `require_mail_transfer`, `target_users`, `popup_enabled`, `status`, `created_at`, `updated_at`) VALUES ('首单折扣', '10%OFF', 10.00, 1, 'all', 1, 1, NOW(), NOW())");
            echo "   ✅ 默认促销活动已插入\n";
        } else {
            echo "   ⏭️  促销活动已存在\n";
        }
        
    } catch (PDOException $e) {
        echo "   ⚠️  " . $e->getMessage() . "\n";
    }
    
    echo "\n========================================\n";
    echo "迁移完成！\n";
    echo "========================================\n\n";
    echo "✅ 成功: {$successCount} 个\n";
    echo "⏭️  跳过: {$skipCount} 个\n";
    echo "❌ 错误: {$errorCount} 个\n\n";
    
    if ($errorCount > 0) {
        echo "⚠️  有 {$errorCount} 个迁移失败，请检查错误信息\n\n";
    } else {
        echo "🎉 所有迁移执行成功！\n\n";
    }
    
} catch (PDOException $e) {
    echo "\n❌ 数据库连接失败: " . $e->getMessage() . "\n";
    echo "\n请检查 .env 文件中的数据库配置：\n";
    echo "  DB_HOST={$host}\n";
    echo "  DB_PORT={$port}\n";
    echo "  DB_DATABASE={$database}\n";
    echo "  DB_USERNAME={$username}\n\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ 发生错误: " . $e->getMessage() . "\n\n";
    exit(1);
}



