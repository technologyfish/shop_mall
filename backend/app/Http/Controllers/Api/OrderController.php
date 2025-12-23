<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\MailTransferSubmission;
use App\Models\UserDiscount;
use App\Models\ShippingSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * 订单列表
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Order::where('user_id', $user->id)->with('items');

        // 状态筛选
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginate($orders);
    }

    /**
     * 订单详情
     */
    public function show($id)
    {
        $user = auth()->user();
        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->with('items.product')
            ->first();

        if (!$order) {
            return $this->error('Order not found', 404);
        }

        return $this->success($order);
    }

    /**
     * 创建订单
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'ship_name' => 'required|string|max:100',
            'ship_email' => 'nullable|email|max:255',
            'ship_phone' => 'required|string|max:20',
            'ship_address' => 'required|string|max:500',
            'ship_city' => 'nullable|string|max:100',
            'ship_postal_code' => 'nullable|string|max:20',
            'remark' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = auth()->user();

        try {
            DB::beginTransaction();

            // 计算订单金额并验证库存
            $totalAmount = 0;
            $orderItems = [];

            foreach ($request->items as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);

                if (!$product || $product->status != 1) {
                    throw new \Exception("Product {$product->name} is not available");
                }

                if (!$product->hasStock($item['quantity'])) {
                    throw new \Exception("Insufficient stock for {$product->name}");
                }

                $itemTotal = $product->price * $item['quantity'];
                $totalAmount += $itemTotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_image' => $product->image,
                    'price' => $product->price,
                    'quantity' => $item['quantity'],
                    'total_amount' => $itemTotal
                ];

                // 减少库存
                $product->decreaseStock($item['quantity']);
            }

            // 1. 计算运费（从全局运费设置获取）
            $shippingFee = 0;
            $shippingSetting = ShippingSetting::where('status', 1)->first();
            
            if ($shippingSetting && $shippingSetting->shipping_fee > 0) {
                // 检查是否达到免运费门槛
                if ($shippingSetting->free_shipping_threshold > 0 && 
                    $totalAmount >= $shippingSetting->free_shipping_threshold) {
                    $shippingFee = 0;  // 达到免运费门槛
                } else {
                    $shippingFee = $shippingSetting->shipping_fee;  // 使用全局运费设置
                }
            }

            // 2. 计算折扣
            $discountAmount = 0;
            $promotionId = null;
            $userDiscountId = null;
            $useFirstOrderDiscount = false;

            // 检查是否是首单
            $isFirstOrder = Order::where('user_id', $user->id)
                ->where('status', '!=', Order::STATUS_CANCELLED)
                ->count() == 0;
            
            \Log::info('First order check', [
                'user_id' => $user->id,
                'is_first_order' => $isFirstOrder,
                'first_order_discount' => $user->first_order_discount
            ]);

            // 优先检查用户的首单优惠（通过 off_code 注册激活的）
            if ($user->first_order_discount == 1 && $isFirstOrder) {
                // 获取首单优惠促销活动（ID=1）
                $firstOrderPromotion = Promotion::find(1);
                
                if ($firstOrderPromotion && $firstOrderPromotion->status == Promotion::STATUS_ACTIVE) {
                    $discountPercentage = $firstOrderPromotion->discount_value / 100;
                    $discountAmount = round($totalAmount * $discountPercentage, 2);
                    $promotionId = 1;
                    $useFirstOrderDiscount = true;
                    
                    \Log::info('First order discount applied', [
                        'user_id' => $user->id,
                        'discount_amount' => $discountAmount,
                        'discount_percentage' => $firstOrderPromotion->discount_value,
                        'total_amount' => $totalAmount
                    ]);
                }
            }
            // 其次检查用户是否有可用的折扣记录（通过 MailTransfer 获取的）
            else {
                $userDiscount = UserDiscount::getAvailableDiscountForUser($user->id);
                
                \Log::info('Checking user discount', [
                    'user_id' => $user->id,
                    'has_discount' => $userDiscount ? true : false,
                    'discount_value' => $userDiscount ? $userDiscount->discount_value : null
                ]);
                
                if ($userDiscount && $isFirstOrder) {
                    // 应用用户折扣（折扣值是百分比，如10表示10%折扣）
                    $discountPercentage = $userDiscount->discount_value / 100;
                    $discountAmount = round($totalAmount * $discountPercentage, 2);
                    $promotionId = $userDiscount->promotion_id;
                    $userDiscountId = $userDiscount->id;
                    
                    \Log::info('Discount applied', [
                        'discount_amount' => $discountAmount,
                        'discount_percentage' => $discountPercentage,
                        'total_amount' => $totalAmount
                    ]);
                }
                // 如果没有用户折扣，检查是否有促销活动（从前端传递）
                else if ($request->has('promotion_id')) {
                    $promotion = Promotion::find($request->promotion_id);
                    
                    if ($promotion && $promotion->status == Promotion::STATUS_ACTIVE && $isFirstOrder) {
                        // 如果活动要求提交MailTransfer表单，检查是否已提交
                        if ($promotion->require_mail_transfer) {
                            $hasSubmitted = MailTransferSubmission::where('email', $user->email)->exists();
                            if ($hasSubmitted) {
                                $discountPercentage = $promotion->discount_value / 100;
                                $discountAmount = round($totalAmount * $discountPercentage, 2);
                                $promotionId = $promotion->id;
                            }
                        } else {
                            // 不需要表单，直接应用折扣
                            $discountPercentage = $promotion->discount_value / 100;
                            $discountAmount = round($totalAmount * $discountPercentage, 2);
                            $promotionId = $promotion->id;
                        }
                    }
                }
            }

            // 3. 计算最终支付金额
            $finalAmount = $totalAmount + $shippingFee - $discountAmount;

            // 创建订单
            $order = Order::create([
                'order_no' => Order::generateOrderNo(),
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $discountAmount,
                'promotion_id' => $promotionId,
                'pay_amount' => $finalAmount,
                'status' => Order::STATUS_PENDING,
                'pay_status' => 0,
                'shipping_name' => $request->ship_name,
                'shipping_email' => $request->ship_email,
                'shipping_phone' => $request->ship_phone,
                'shipping_address' => $request->ship_address,
                'shipping_city' => $request->ship_city,
                'shipping_postal_code' => $request->ship_postal_code,
                'remark' => $request->remark
            ]);

            // 创建订单商品
            foreach ($orderItems as $item) {
                $item['order_id'] = $order->id;
                OrderItem::create($item);
            }

            // 清空购物车（如果从购物车下单）
            if ($request->has('clear_cart') && $request->clear_cart) {
                Cart::where('user_id', $user->id)->delete();
            }

            // 如果使用了用户折扣，标记为已使用
            if ($userDiscountId) {
                $userDiscount->markAsUsed($order->id);
            }
            
            // 如果使用了首单优惠（通过 off_code），关闭首单优惠状态
            if ($useFirstOrderDiscount) {
                $user->first_order_discount = 0;
                $user->save();
                
                \Log::info('First order discount used and disabled', [
                    'user_id' => $user->id,
                    'order_id' => $order->id
                ]);
            }

            DB::commit();

            return $this->success($order->load('items'), 'Order created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * 取消订单
     */
    public function cancel($id)
    {
        $user = auth()->user();
        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return $this->error('Order not found', 404);
        }

        if ($order->status != Order::STATUS_PENDING) {
            return $this->error('Order cannot be cancelled', 400);
        }

        try {
            DB::beginTransaction();

            // 恢复库存
            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('stock', $item->quantity);
                }
            }

            // 如果订单使用了首单折扣（promotion_id=1），恢复用户的首单折扣状态
            if ($order->promotion_id == 1 && $order->discount_amount > 0) {
                $user->first_order_discount = 1;
                $user->save();
                
                \Log::info('First order discount restored for cancelled order', [
                    'user_id' => $user->id,
                    'order_id' => $order->id
                ]);
            }

            $order->status = Order::STATUS_CANCELLED;
            $order->save();

            DB::commit();

            return $this->success($order, 'Order cancelled successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), 400);
        }
    }

}



