<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelUnpaidOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cancel-unpaid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel unpaid orders older than 1 minute (test mode)';

    // 超时时间：1分钟（测试用），正式环境改为 60（分钟）
    const TIMEOUT_MINUTES = 1;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for unpaid orders (timeout: ' . self::TIMEOUT_MINUTES . ' minutes)...');

        try {
            // 查找超时未支付的订单
            $orders = Order::where('status', Order::STATUS_PENDING)
                ->where('pay_status', 0)
                ->where('created_at', '<', now()->subMinutes(self::TIMEOUT_MINUTES))
                ->get();
        } catch (\Exception $e) {
            $this->error('Failed to fetch orders: ' . $e->getMessage());
            Log::error('Failed to fetch unpaid orders: ' . $e->getMessage());
            return 1;
        }

        if ($orders->isEmpty()) {
            $this->info('No orders to cancel.');
            return;
        }

        $count = 0;
        foreach ($orders as $order) {
            try {
                DB::beginTransaction();

                // 恢复库存
                foreach ($order->items as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->increment('stock', $item->quantity);
                    }
                }

                // 如果订单使用了首单折扣，恢复用户的首单折扣状态
                if ($order->promotion_id == 1 && $order->discount_amount > 0) {
                    $user = User::find($order->user_id);
                    if ($user) {
                        $user->first_order_discount = 1;
                        $user->save();
                        Log::info("First order discount restored for user {$user->id} due to order timeout");
                    }
                }

                $order->status = Order::STATUS_CANCELLED;
                $order->remark = $order->remark . " [System: Auto cancelled due to timeout]";
                $order->save();

                DB::commit();
                $count++;
                
                Log::info("Order {$order->order_no} auto cancelled.");
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to auto cancel order {$order->order_no}: " . $e->getMessage());
            }
        }

        $this->info("Cancelled {$count} orders.");
    }
}

