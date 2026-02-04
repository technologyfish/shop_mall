<?php
/**
 * 升级收货地址相关的表结构
 * 运行方式：php backend/database/update_address_schema.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== 开始更新收货地址表结构 ===\n\n";

try {
    // 1. 更新 addresses 表
    echo "正在更新 addresses 表...\n";
    
    // 检查字段是否存在，不存在则添加
    $columns = DB::select("SHOW COLUMNS FROM addresses");
    $existing = array_column($columns, 'Field');

    $queries = [];
    
    if (!in_array('first_name', $existing)) {
        $queries[] = "ADD COLUMN first_name VARCHAR(100) AFTER user_id";
    }
    if (!in_array('last_name', $existing)) {
        $queries[] = "ADD COLUMN last_name VARCHAR(100) AFTER first_name";
    }
    if (!in_array('country', $existing)) {
        $queries[] = "ADD COLUMN country VARCHAR(100) DEFAULT 'United Kingdom' AFTER last_name";
    }
    if (!in_array('apartment', $existing)) {
        $queries[] = "ADD COLUMN apartment VARCHAR(255) NULL AFTER address";
    }
    if (in_array('postal_code', $existing)) {
        $queries[] = "CHANGE COLUMN postal_code postcode VARCHAR(20)";
    } else if (!in_array('postcode', $existing)) {
        $queries[] = "ADD COLUMN postcode VARCHAR(20) AFTER city";
    }

    if (!empty($queries)) {
        $sql = "ALTER TABLE addresses " . implode(", ", $queries);
        DB::statement($sql);
        echo "✅ addresses 表更新成功\n";
    } else {
        echo "ℹ️ addresses 表已是最新状态\n";
    }

    // 2. 更新 orders 表
    echo "\n正在更新 orders 表...\n";
    $columns = DB::select("SHOW COLUMNS FROM orders");
    $existing = array_column($columns, 'Field');

    $queries = [];
    
    if (!in_array('shipping_first_name', $existing)) {
        $queries[] = "ADD COLUMN shipping_first_name VARCHAR(100) AFTER user_id";
    }
    if (!in_array('shipping_last_name', $existing)) {
        $queries[] = "ADD COLUMN shipping_last_name VARCHAR(100) AFTER shipping_first_name";
    }
    if (!in_array('shipping_country', $existing)) {
        $queries[] = "ADD COLUMN shipping_country VARCHAR(100) DEFAULT 'United Kingdom' AFTER shipping_last_name";
    }
    if (!in_array('shipping_apartment', $existing)) {
        $queries[] = "ADD COLUMN shipping_apartment VARCHAR(255) NULL AFTER shipping_address";
    }
    if (in_array('shipping_postal_code', $existing)) {
        $queries[] = "CHANGE COLUMN shipping_postal_code shipping_postcode VARCHAR(20)";
    } else if (!in_array('shipping_postcode', $existing)) {
        $queries[] = "ADD COLUMN shipping_postcode VARCHAR(20) AFTER shipping_city";
    }

    if (!empty($queries)) {
        $sql = "ALTER TABLE orders " . implode(", ", $queries);
        DB::statement($sql);
        echo "✅ orders 表更新成功\n";
    } else {
        echo "ℹ️ orders 表已是最新状态\n";
    }

    echo "\n=== 所有更新完成 ===\n";

} catch (\Exception $e) {
    echo "\n❌ 更新失败: " . $e->getMessage() . "\n";
    exit(1);
}







