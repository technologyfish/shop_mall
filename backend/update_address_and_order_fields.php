<?php
/**
 * 更新地址和订单相关表字段
 * 添加: email, city, postal_code
 */

require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

try {
    echo "Starting to update address and order fields...\n\n";
    
    // 1. 更新addresses表
    echo "1. Updating addresses table...\n";
    $addressSql = file_get_contents(__DIR__ . '/database/migrations/update_address_fields.sql');
    
    // 分割多个SQL语句并执行
    $addressStatements = array_filter(array_map('trim', explode(';', $addressSql)));
    foreach ($addressStatements as $statement) {
        if (!empty($statement) && !str_starts_with(trim($statement), '--')) {
            DB::statement($statement);
            echo "  ✓ Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    echo "  ✓ Addresses table updated successfully!\n\n";
    
    // 2. 更新orders表
    echo "2. Updating orders table...\n";
    $orderSql = file_get_contents(__DIR__ . '/database/migrations/update_order_shipping_fields.sql');
    
    $orderStatements = array_filter(array_map('trim', explode(';', $orderSql)));
    foreach ($orderStatements as $statement) {
        if (!empty($statement) && !str_starts_with(trim($statement), '--')) {
            DB::statement($statement);
            echo "  ✓ Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    echo "  ✓ Orders table updated successfully!\n\n";
    
    // 3. 更新subscription_orders表
    echo "3. Updating subscription_orders table...\n";
    $subOrderSql = file_get_contents(__DIR__ . '/database/migrations/update_subscription_order_shipping_fields.sql');
    
    $subOrderStatements = array_filter(array_map('trim', explode(';', $subOrderSql)));
    foreach ($subOrderStatements as $statement) {
        if (!empty($statement) && !str_starts_with(trim($statement), '--')) {
            DB::statement($statement);
            echo "  ✓ Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    echo "  ✓ Subscription_orders table updated successfully!\n\n";
    
    echo "✅ All migrations completed successfully!\n";
    echo "\nUpdated tables:\n";
    echo "- addresses: added email, city, postal_code\n";
    echo "- orders: added shipping_email, shipping_city, shipping_postal_code\n";
    echo "- subscription_orders: added shipping_email, shipping_city, shipping_postal_code\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}






