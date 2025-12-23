<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\EmailTask;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * 创建支付
     */
    public function create(Request $request)
    {
        // 记录请求数据用于调试
        \Log::info('Payment create request', [
            'data' => $request->all(),
            'user_id' => auth()->id()
        ]);

        $validator = Validator::make($request->all(), [
            'order_no' => 'required|string',
            'payment_method' => 'required|in:bank_card,alipay,wechat',
            'return_url' => 'nullable|url',
            'callback_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            \Log::error('Payment validation failed', [
                'errors' => $validator->errors()->toArray(),
                'data' => $request->all()
            ]);
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = auth()->user();
        $order = Order::where('order_no', $request->order_no)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return $this->error('Order not found', 404);
        }

        if ($order->pay_status == 1) {
            return $this->error('Order already paid', 400);
        }

        try {
            DB::beginTransaction();

            // 创建或更新支付记录
            $payment = Payment::updateOrCreate(
                ['order_no' => $order->order_no],
                [
            'order_id' => $order->id,
            'order_no' => $order->order_no,
                    'pay_method' => $request->payment_method,
            'pay_amount' => $order->pay_amount,
                    'status' => Payment::STATUS_PENDING,
                    'return_url' => $request->return_url,
                ]
            );

            // 调用第三方支付接口生成支付URL
            $paymentUrl = $this->getPaymentUrl($payment, $request->return_url, $request->callback_url);

            DB::commit();

        return $this->success([
            'payment_id' => $payment->id,
                'payment_url' => $paymentUrl,
                'order_no' => $order->order_no,
        ], 'Payment created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * 支付回调
     */
    public function callback(Request $request)
    {
        // TODO: 验证支付平台的签名
        // 这里需要根据实际的支付平台实现

        $orderNo = $request->input('order_no');
        $paymentNo = $request->input('payment_no');
        $status = $request->input('status');

        try {
            DB::beginTransaction();

            $payment = Payment::where('order_no', $orderNo)->first();
            if (!$payment) {
                throw new \Exception('Payment not found');
            }

            $order = $payment->order;

            if ($status == 'success') {
                // 更新支付记录
                $payment->update([
                    'payment_no' => $paymentNo,
                    'status' => Payment::STATUS_SUCCESS,
                    'pay_time' => now(),
                    'callback_data' => json_encode($request->all())
                ]);

                // 更新订单状态（支付成功后改为待发货=1）
                $order->update([
                    'status' => 1, // 待发货
                    'pay_status' => 1,
                    'pay_method' => $payment->pay_method,
                    'pay_time' => now()
                ]);

                // 增加商品销量
                foreach ($order->items as $item) {
                    $product = $item->product;
                    if ($product) {
                        $product->increaseSales($item->quantity);
                    }
                }
            } else {
                $payment->update([
                    'status' => Payment::STATUS_FAILED,
                    'callback_data' => json_encode($request->all())
                ]);
            }

            DB::commit();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 查询支付状态（简化调试版本）
     */
    public function status($orderNo)
    {
        try {
        $user = auth()->user();
            if (!$user) {
                \Log::error('User not authenticated');
                return response()->json([
                    'code' => 401,
                    'message' => 'Unauthorized',
                    'data' => null
                ], 401);
            }

        $order = Order::where('order_no', $orderNo)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
                \Log::error('Order not found', ['order_no' => $orderNo]);
                return response()->json([
                    'code' => 404,
                    'message' => 'Order not found',
                    'data' => null
                ], 404);
            }

            // 如果订单未支付，但有session_id参数，尝试从Stripe验证
            $sessionId = request()->input('session_id');
            if ($order->pay_status != 1 && $sessionId) {
                \Log::info('Attempting to verify payment via session_id', [
                    'order_no' => $orderNo,
                    'session_id' => $sessionId
                ]);
                
                if (class_exists('\Stripe\Stripe')) {
                    try {
                        $stripeKey = env('STRIPE_SECRET_KEY');
                        if ($stripeKey) {
                            \Stripe\Stripe::setApiKey($stripeKey);
                            $session = \Stripe\Checkout\Session::retrieve($sessionId);
                            
                            \Log::info('Stripe session retrieved', [
                                'payment_status' => $session->payment_status,
                                'session_id' => $sessionId
                            ]);
                            
                            // 如果Stripe显示已支付，更新本地订单状态
                            if ($session->payment_status === 'paid') {
                                DB::beginTransaction();
                                try {
                                    // 完全使用原生SQL，与手动SQL保持一致
                                    $updateOrderSql = "
                                        UPDATE orders 
                                        SET pay_status = 1,
                                            status = 1,
                                            payment_status = 'paid',
                                            payment_method = 'stripe',
                                            payment_no = ?,
                                            paid_at = NOW(),
                                            updated_at = NOW()
                                        WHERE order_no = ?
                                    ";
                                    DB::update($updateOrderSql, [$session->payment_intent, $orderNo]);
                                    
                                    \Log::info('Order updated', ['order_no' => $orderNo]);
                                    
                                    // 更新支付记录（status字段是整型：0待支付 1成功 2失败）
                                    $updatePaymentSql = "
                                        UPDATE payments 
                                        SET status = 1,
                                            payment_no = ?,
                                            pay_time = NOW(),
                                            updated_at = NOW()
                                        WHERE order_no = ?
                                    ";
                                    DB::update($updatePaymentSql, [$session->payment_intent, $orderNo]);
                                    
                                    \Log::info('Payment updated', ['order_no' => $orderNo]);
                                    
                                    // 增加商品销量
                                    $orderItems = DB::select("
                                        SELECT product_id, quantity 
                                        FROM order_items 
                                        WHERE order_id = ?
                                    ", [$order->id]);
                                    
                                    foreach ($orderItems as $item) {
                                        if ($item->product_id) {
                                            DB::update("
                                                UPDATE products 
                                                SET sales = sales + ? 
                                                WHERE id = ?
                                            ", [$item->quantity, $item->product_id]);
                                        }
                                    }
                                    
                                    \Log::info('Product sales updated', ['order_no' => $orderNo]);
                                    
                                    DB::commit();
                                    
                                    \Log::info('✅ Payment verified and order updated successfully', [
                                        'order_no' => $orderNo,
                                        'session_id' => $sessionId
                                    ]);
                                    
                                    // 重新获取订单
                                    $order = Order::where('order_no', $orderNo)->first();

                                    // 发送订单确认邮件
                                    $this->sendOrderConfirmationEmail($order);
                                    
                                } catch (\Exception $e) {
                                    DB::rollBack();
                                    \Log::error('❌ Failed to update order', [
                                        'order_no' => $orderNo,
                                        'error' => $e->getMessage(),
                                        'line' => $e->getLine(),
                                        'file' => $e->getFile(),
                                        'trace' => $e->getTraceAsString()
                                    ]);
                                    
                                    // 将错误信息也返回给前端
                                    throw $e;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::error('Stripe verification failed', [
                            'error' => $e->getMessage(),
                            'line' => $e->getLine()
                        ]);
                    }
                }
            }

            // 获取支付记录
            $payment = Payment::where('order_no', $orderNo)->first();
            
            return response()->json([
                'code' => 0,
                'message' => 'Success',
                'data' => [
                    'order_id' => $order->id,
            'order_no' => $order->order_no,
                    'amount' => $order->pay_amount,
            'pay_status' => $order->pay_status,
                    'status' => $order->pay_status == 1 ? 'paid' : 'pending',
                    'payment_method' => $payment ? $payment->pay_method : null,
                    'paid_at' => $order->paid_at,
                ]
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Payment status check exception', [
                'order_no' => $orderNo ?? 'unknown',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'code' => 500,
                'message' => 'Server error: ' . $e->getMessage(),
                'data' => [
                    'error' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => basename($e->getFile())
                ]
            ], 500);
        }
    }

    /**
     * 获取支付URL
     * 
     * 支持两种模式：
     * 1. 开发模式(mock)：模拟支付页面
     * 2. 生产模式(live)：真实支付网关
     */
    private function getPaymentUrl($payment, $returnUrl = null, $callbackUrl = null)
    {
        $mode = env('PAYMENT_MODE', 'mock');
        
        if ($mode === 'mock') {
            // 模拟支付页面（开发测试用）
            return url('/mock-payment?' . http_build_query([
                'order_no' => $payment->order_no,
                'amount' => $payment->pay_amount,
                'return_url' => $returnUrl ?: url('/payment/result?order_no=' . $payment->order_no),
            ]));
        }
        
        // 生产环境 - 真实支付网关
        if ($payment->pay_method === 'bank_card') {
            return $this->createStripePayment($payment, $returnUrl, $callbackUrl);
        }
        
        // 其他支付方式可以在这里添加
        // if ($payment->pay_method === 'alipay') {
        //     return $this->createAlipayPayment($payment, $returnUrl, $callbackUrl);
        // }
        
        throw new \Exception('Unsupported payment method: ' . $payment->pay_method);
    }
    
    /**
     * 创建Stripe支付
     */
    private function createStripePayment($payment, $returnUrl, $callbackUrl)
    {
        try {
            // 检查SDK是否已安装
            if (!class_exists('\Stripe\Stripe')) {
                throw new \Exception('Stripe SDK not installed. Run: composer require stripe/stripe-php');
            }
            
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            
            // 获取订单信息用于显示
            $order = Order::find($payment->order_id);
            
            // 准备商品信息
            $lineItems = [];
            
            // 计算订单总额（英镑，转为便士）- pay_amount 已包含折扣
            $totalAmountCents = (int)($payment->pay_amount * 100);
            
            // Stripe最低金额要求：£0.30 (30便士)
            if ($totalAmountCents < 30) {
                \Log::warning('Order amount too low for Stripe', [
                    'order_no' => $payment->order_no,
                    'original_amount' => $payment->pay_amount,
                    'adjusted_to' => 0.30
                ]);
                $totalAmountCents = 30; // 调整为最低金额 £0.30
            }
            
            \Log::info('Stripe payment amount', [
                'order_no' => $payment->order_no,
                'pay_amount' => $payment->pay_amount,
                'total_amount_cents' => $totalAmountCents,
                'discount_amount' => $order->discount_amount ?? 0
            ]);
            
            // 使用订单实付金额 pay_amount，该金额已经包含折扣
            // 这样确保 Stripe 收取的金额与系统显示的一致
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'gbp', // 英镑
                    'product_data' => [
                        'name' => 'Order #' . $payment->order_no,
                        'description' => $this->getOrderDescription($order),
                    ],
                    'unit_amount' => $totalAmountCents, // 使用 pay_amount（已含折扣）
                ],
                'quantity' => 1,
            ];
            
            // 创建Stripe Checkout Session
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $returnUrl . (strpos($returnUrl, '?') !== false ? '&' : '?') . 'session_id={CHECKOUT_SESSION_ID}&status=success&order_no=' . $payment->order_no,
                'cancel_url' => $returnUrl . (strpos($returnUrl, '?') !== false ? '&' : '?') . 'status=failed&order_no=' . $payment->order_no,
                'client_reference_id' => $payment->order_no,
                'customer_email' => auth()->user()->email ?? null,
                'metadata' => [
                    'order_id' => $payment->order_id,
                    'order_no' => $payment->order_no,
                    'user_id' => auth()->id(),
                ],
                'payment_intent_data' => [
                    'metadata' => [
                        'order_no' => $payment->order_no,
                    ],
                ],
            ]);
            
            // 保存session_id到支付记录
            $payment->update([
                'payment_no' => $session->id,
            ]);
            
            \Log::info('Stripe session created', [
                'session_id' => $session->id,
            'order_no' => $payment->order_no,
            'amount' => $payment->pay_amount,
            ]);
            
            return $session->url;
            
        } catch (\Exception $e) {
            \Log::error('Stripe payment creation failed', [
                'error' => $e->getMessage(),
                'order_no' => $payment->order_no,
            ]);
            throw $e;
        }
    }
    
    /**
     * Stripe Webhook回调处理
     */
    public function stripeWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = env('STRIPE_WEBHOOK_SECRET');
        
        if (!$webhookSecret) {
            \Log::error('Stripe webhook secret not configured');
            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }
        
        try {
            // 验证webhook签名
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            \Log::error('Stripe webhook invalid payload: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            \Log::error('Stripe webhook invalid signature: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }
        
        \Log::info('Stripe webhook received', [
            'type' => $event->type,
            'id' => $event->id,
        ]);
        
        // 处理不同类型的事件
        try {
            switch ($event->type) {
                case 'checkout.session.completed':
                    $session = $event->data->object;
                    $this->handleCheckoutSessionCompleted($session);
                    break;
                    
                case 'checkout.session.async_payment_succeeded':
                    $session = $event->data->object;
                    $this->handleCheckoutSessionCompleted($session);
                    break;
                    
                case 'checkout.session.async_payment_failed':
                    $session = $event->data->object;
                    $this->handleCheckoutSessionFailed($session);
                    break;
                    
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    \Log::info('Payment intent succeeded', ['id' => $paymentIntent->id]);
                    break;
                    
                case 'payment_intent.payment_failed':
                    $paymentIntent = $event->data->object;
                    $this->handlePaymentIntentFailed($paymentIntent);
                    break;
                    
                default:
                    \Log::info('Unhandled webhook event type: ' . $event->type);
            }
            
            return response()->json(['received' => true]);
            
        } catch (\Exception $e) {
            \Log::error('Stripe webhook processing failed', [
                'error' => $e->getMessage(),
                'event_type' => $event->type,
            ]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
    
    /**
     * 处理Checkout Session完成
     */
    private function handleCheckoutSessionCompleted($session)
    {
        $orderNo = $session->client_reference_id ?? $session->metadata->order_no ?? null;
        
        if (!$orderNo) {
            throw new \Exception('Order number not found in session');
        }
        
        DB::beginTransaction();
        try {
            $order = Order::where('order_no', $orderNo)->first();
            if (!$order) {
                throw new \Exception('Order not found: ' . $orderNo);
            }
            
            // 检查是否已处理（防止重复）
            if ($order->pay_status == 1) {
                \Log::info('Order already paid, skipping: ' . $orderNo);
                DB::commit();
                return;
            }
            
            // 更新订单状态（支付成功后改为待发货=1）
            $order->update([
                'status' => 1, // 待发货
                'pay_status' => 1,
                'payment_status' => 'paid',
                'payment_method' => 'stripe',
                'payment_no' => $session->payment_intent ?? $session->id,
                'paid_at' => now(),
            ]);
            
            // 更新支付记录
            Payment::where('order_no', $orderNo)->update([
                'status' => 'success',
                'payment_no' => $session->payment_intent ?? $session->id,
                'pay_time' => now(),
            ]);
            
            // 增加商品销量
            foreach ($order->items as $item) {
                $product = $item->product;
                if ($product) {
                    $product->increment('sales', $item->quantity);
                }
            }
            
            DB::commit();
            
            // 发送订单确认邮件
            $this->sendOrderConfirmationEmail($order);

            \Log::info('Payment processed successfully', [
                'order_no' => $orderNo,
                'amount' => $order->pay_amount,
                'session_id' => $session->id,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Payment processing failed', [
                'order_no' => $orderNo,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * 处理Checkout Session失败
     */
    private function handleCheckoutSessionFailed($session)
    {
        $orderNo = $session->client_reference_id ?? $session->metadata->order_no ?? null;
        
        if (!$orderNo) {
            return;
        }
        
        try {
            Payment::where('order_no', $orderNo)->update([
                'status' => 'failed',
            ]);
            
            \Log::warning('Payment failed', [
                'order_no' => $orderNo,
                'session_id' => $session->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed payment handling error: ' . $e->getMessage());
        }
    }
    
    /**
     * 处理Payment Intent失败
     */
    private function handlePaymentIntentFailed($paymentIntent)
    {
        $orderNo = $paymentIntent->metadata->order_no ?? null;
        
        if (!$orderNo) {
            return;
        }
        
        try {
            Payment::where('order_no', $orderNo)->update([
                'status' => 'failed',
            ]);
            
            \Log::warning('Payment intent failed', [
                'order_no' => $orderNo,
                'payment_intent_id' => $paymentIntent->id,
                'error' => $paymentIntent->last_payment_error->message ?? 'Unknown error',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed payment intent handling error: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取订单描述（用于Stripe显示）
     */
    private function getOrderDescription($order)
    {
        if (!$order || !$order->items || $order->items->isEmpty()) {
            return 'Order payment';
        }
        
        $items = [];
        foreach ($order->items as $item) {
            $items[] = $item->product_name . ' x ' . $item->quantity;
        }
        
        $desc = implode(', ', $items);
        
        // 添加折扣信息
        if ($order->discount_amount && floatval($order->discount_amount) > 0) {
            $desc .= ' (Discount: -£' . number_format($order->discount_amount, 2) . ')';
        }
        
        return strlen($desc) > 500 ? substr($desc, 0, 497) . '...' : $desc;
    }
    
    /**
     * 发送订单确认邮件
     */
    private function sendOrderConfirmationEmail($order)
    {
        try {
            $user = $order->user;
            if (!$user || !$user->email) return;

            $task = EmailTask::where('type', EmailTask::TYPE_ORDER_CONFIRMATION)
                ->where('status', EmailTask::STATUS_ENABLED)
                ->first();

            if ($task) {
                $variables = [
                    '{username}' => $user->username,
                    '{order_no}' => $order->order_no,
                    '{total_amount}' => number_format($order->total_amount, 2),
                    '{pay_amount}' => number_format($order->pay_amount, 2),
                    '{shipping_name}' => $order->shipping_name,
                    '{shipping_address}' => $order->shipping_address,
                    '{order_link}' => env('APP_FRONTEND_URL', 'http://localhost:5173') . '/user-center/orders/' . $order->id,
                ];
                $subject = str_replace(array_keys($variables), array_values($variables), $task->subject);
                $body = str_replace(array_keys($variables), array_values($variables), $task->content);

                MailService::sendHtmlMail($user->email, $subject, $body);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send order confirmation email', ['error' => $e->getMessage()]);
        }
    }
}
