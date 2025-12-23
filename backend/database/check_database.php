<?php
/**
 * 数据库表结构检查工具
 * 运行方式：php database/check_database.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== 数据库表结构检查 ===\n\n";

// 检查 orders 表
echo "检查 orders 表结构:\n";
$ordersColumns = DB::select("SHOW COLUMNS FROM orders");
echo "现有字段:\n";
foreach ($ordersColumns as $column) {
    echo "  - {$column->Field} ({$column->Type})\n";
}

// 检查必需字段
$requiredOrdersColumns = [
    'shipping_company',
    'shipping_no',
    'shipping_email',
    'shipping_city',
    'shipping_postal_code'
];

echo "\n缺失字段检查:\n";
$existingColumns = array_column($ordersColumns, 'Field');
foreach ($requiredOrdersColumns as $col) {
    if (!in_array($col, $existingColumns)) {
        echo "  ❌ 缺失: {$col}\n";
    } else {
        echo "  ✅ 存在: {$col}\n";
    }
}

// 检查 addresses 表
echo "\n\n检查 addresses 表结构:\n";
$addressesColumns = DB::select("SHOW COLUMNS FROM addresses");
echo "现有字段:\n";
foreach ($addressesColumns as $column) {
    echo "  - {$column->Field} ({$column->Type})\n";
}

// 检查必需字段
$requiredAddressesColumns = ['email', 'city', 'postal_code'];
echo "\n缺失字段检查:\n";
$existingColumns = array_column($addressesColumns, 'Field');
foreach ($requiredAddressesColumns as $col) {
    if (!in_array($col, $existingColumns)) {
        echo "  ❌ 缺失: {$col}\n";
    } else {
        echo "  ✅ 存在: {$col}\n";
    }
}

// 检查 subscriptions 表
echo "\n\n检查 subscriptions 表结构:\n";
$subscriptionsColumns = DB::select("SHOW COLUMNS FROM subscriptions");
echo "现有字段:\n";
foreach ($subscriptionsColumns as $column) {
    echo "  - {$column->Field} ({$column->Type})\n";
}

echo "\n\n=== 检查完成 ===\n";




