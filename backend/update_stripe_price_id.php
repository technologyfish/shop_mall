<?php
/**
 * 更新订阅计划的Stripe Price ID
 * 
 * 使用方法：
 * php update_stripe_price_id.php <plan_id> <price_id>
 * 
 * 示例：
 * php update_stripe_price_id.php 1 price_1OabcdEF123456
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

// 获取命令行参数
$planId = $argv[1] ?? null;
$priceId = $argv[2] ?? null;

if (!$planId || !$priceId) {
    echo "❌ 使用方法错误！\n\n";
    echo "正确用法：\n";
    echo "php update_stripe_price_id.php <plan_id> <price_id>\n\n";
    echo "示例：\n";
    echo "php update_stripe_price_id.php 1 price_1OabcdEF123456\n\n";
    echo "当前订阅计划：\n";
    
    $plans = DB::table('subscription_plans')->get();
    foreach ($plans as $plan) {
        echo "  ID: {$plan->id} - {$plan->name}\n";
    }
    
    exit(1);
}

try {
    // 检查计划是否存在
    $plan = DB::table('subscription_plans')->where('id', $planId)->first();
    
    if (!$plan) {
        echo "❌ 找不到ID为 {$planId} 的订阅计划\n";
        exit(1);
    }
    
    // 更新Price ID
    DB::table('subscription_plans')
        ->where('id', $planId)
        ->update(['stripe_price_id' => $priceId]);
    
    echo "✅ 成功更新订阅计划！\n\n";
    echo "计划ID: {$planId}\n";
    echo "计划名称: {$plan->name}\n";
    echo "Stripe Price ID: {$priceId}\n";
    
} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    exit(1);
}




