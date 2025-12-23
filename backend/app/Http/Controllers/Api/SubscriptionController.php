<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    /**
     * 获取所有订阅计划
     */
    public function getPlans()
    {
        // 获取订阅计划
        $plans = SubscriptionPlan::where('status', SubscriptionPlan::STATUS_ACTIVE)
            ->orderBy('sort', 'asc')
            ->orderBy('price', 'asc')
            ->get();

        return $this->success($plans);
    }

    /**
     * 创建订阅（Stripe Checkout）
     * 参数：plan_id（订阅计划ID）
     */
    public function create(Request $request)
    {
        $this->validate($request, [
            'plan_id' => 'required|integer|exists:subscription_plans,id',
        ]);

        $user = auth()->user();
        
        // 检查用户是否已有活跃订阅
        $existingSubscription = Subscription::where('user_id', $user->id)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_PAST_DUE])
            ->first();

        if ($existingSubscription) {
            return $this->error('You already have an active subscription', 400);
        }

        // 获取订阅计划
        $plan = SubscriptionPlan::where('id', $request->plan_id)
            ->where('status', SubscriptionPlan::STATUS_ACTIVE)
            ->first();

        if (!$plan || !$plan->stripe_price_id) {
            return $this->error('Invalid subscription plan or Stripe price not configured', 400);
        }

        try {
            // 初始化Stripe
            if (!class_exists('\Stripe\Stripe')) {
                throw new \Exception('Stripe SDK not installed');
            }

            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

            // 创建或获取Stripe Customer
            $stripeCustomerId = $user->stripe_customer_id ?? null;

            if (!$stripeCustomerId) {
                $customer = \Stripe\Customer::create([
                    'email' => $user->email,
                    'name' => $user->username,
                    'metadata' => [
                        'user_id' => $user->id,
                    ],
                ]);
                $stripeCustomerId = $customer->id;

                // 保存customer_id到用户表
                $user->stripe_customer_id = $stripeCustomerId;
                $user->save();
            }

            // 创建Stripe Checkout Session用于订阅
            $session = \Stripe\Checkout\Session::create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $plan->stripe_price_id,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => env('APP_FRONTEND_URL', 'http://localhost:5173') . '/subscription/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => env('APP_FRONTEND_URL', 'http://localhost:5173') . '/subscription/cancelled',
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                ],
            ]);

            return $this->success([
                'session_id' => $session->id,
                'checkout_url' => $session->url,
            ]);

        } catch (\Exception $e) {
            \Log::error('Subscription creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'plan_id' => $request->plan_id,
            ]);
            return $this->error('Failed to create subscription: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 验证订阅支付并创建订阅记录（前端支付成功后调用）
     */
    public function verify(Request $request)
    {
        $this->validate($request, [
            'session_id' => 'required|string',
        ]);

        $user = auth()->user();

        try {
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

            // 从Stripe获取Session详情
            $session = \Stripe\Checkout\Session::retrieve($request->session_id);

            \Log::info('Verifying subscription session', [
                'session_id' => $session->id,
                'mode' => $session->mode,
                'payment_status' => $session->payment_status,
                'subscription' => $session->subscription,
            ]);

            // 验证是否为订阅支付且已完成
            if ($session->mode !== 'subscription') {
                return $this->error('This is not a subscription payment', 400);
            }

            if ($session->payment_status !== 'paid') {
                return $this->error('Payment not completed', 400);
            }

            // 验证用户
            $sessionUserId = $session->metadata->user_id ?? null;
            if ($sessionUserId != $user->id) {
                return $this->error('Session does not belong to current user', 403);
            }

            $stripeSubscriptionId = $session->subscription;
            if (!$stripeSubscriptionId) {
                return $this->error('No subscription found in session', 400);
            }

            // 检查订阅是否已存在
            $existingSubscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();
            if ($existingSubscription) {
                return $this->success($existingSubscription, 'Subscription already exists');
            }

            // 从Stripe获取订阅详情
            $stripeSubscription = \Stripe\Subscription::retrieve($stripeSubscriptionId);

            // 获取计划信息
            $planId = $session->metadata->plan_id ?? null;
            $plan = SubscriptionPlan::find($planId);

            if (!$plan) {
                // 尝试通过price_id查找
                $priceId = $stripeSubscription->items->data[0]->price->id;
                $plan = SubscriptionPlan::where('stripe_price_id', $priceId)->first();
            }

            if (!$plan) {
                return $this->error('Subscription plan not found', 400);
            }

            // 创建订阅记录
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'stripe_subscription_id' => $stripeSubscriptionId,
                'stripe_customer_id' => $session->customer,
                'plan_name' => $plan->name,
                'plan_type' => $plan->plan_type,
                'bottles_per_delivery' => $plan->bottles_per_delivery,
                'price' => $plan->price,
                'status' => Subscription::STATUS_ACTIVE,
                'current_period_start' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_start),
                'current_period_end' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end),
                'next_delivery_date' => null,
            ]);

            // 更新用户为订阅用户
            $user->is_subscriber = 1;
            $user->save();

            // 创建订阅订单
            $this->createSubscriptionOrder($user, $plan, $subscription);

            \Log::info('Subscription created via verification', [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
            ]);

            return $this->success($subscription, 'Subscription created successfully');

        } catch (\Exception $e) {
            \Log::error('Subscription verification failed', [
                'error' => $e->getMessage(),
                'session_id' => $request->session_id,
            ]);
            return $this->error('Failed to verify subscription: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 创建订阅订单（私有方法）
     */
    private function createSubscriptionOrder($user, $plan, $subscription)
    {
        // 生成订单号
        $orderNo = 'SUB' . date('YmdHis') . rand(1000, 9999);

        // 获取用户默认地址
        $address = \App\Models\Address::where('user_id', $user->id)
            ->where('is_default', 1)
            ->first();

        if (!$address) {
            $address = \App\Models\Address::where('user_id', $user->id)->first();
        }

        // 创建订单
        $order = \App\Models\Order::create([
            'order_no' => $orderNo,
            'user_id' => $user->id,
            'total_amount' => $plan->price,
            'pay_amount' => $plan->price,
            'pay_status' => 1,
            'status' => 1,
            'is_subscription' => 1,
            'subscription_id' => $subscription->id,
            'shipping_name' => $address->name ?? $user->username,
            'shipping_email' => $address->email ?? $user->email,
            'shipping_phone' => $address->phone ?? $user->phone,
            'shipping_address' => $address->address ?? 'Pending address',
            'shipping_city' => $address->city ?? null,
            'shipping_postal_code' => $address->postal_code ?? null,
            'payment_method' => 'stripe_subscription',
            'payment_no' => $subscription->stripe_subscription_id,
            'paid_at' => \Carbon\Carbon::now(),
        ]);

        // 创建订单项
        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'product_id' => 0,
            'product_name' => $plan->name . ' - ' . $plan->description,
            'product_image' => '',
            'price' => $plan->price,
            'quantity' => 1,
            'total_amount' => $plan->price,
        ]);

        \Log::info('Subscription order created', [
            'order_id' => $order->id,
            'order_no' => $orderNo,
            'subscription_id' => $subscription->id,
        ]);

        return $order;
    }

    /**
     * 获取用户的订阅列表 (index 方法)
     */
    public function index()
    {
        $user = auth()->user();
        
        $subscriptions = Subscription::where('user_id', $user->id)
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'plan_name' => $subscription->plan->name ?? 'Unknown Plan',
                    'plan_type' => $subscription->plan->type ?? 'monthly',
                    'price' => $subscription->plan->price ?? 0,
                    'bottles_per_delivery' => $subscription->plan->bottles_per_delivery ?? 1,
                    'status' => $subscription->status,
                    'next_delivery_date' => $subscription->next_delivery_date,
                    'current_period_start' => $subscription->current_period_start,
                    'current_period_end' => $subscription->current_period_end,
                    'created_at' => $subscription->created_at,
                ];
            });

        return $this->success($subscriptions);
    }

    /**
     * 获取用户的订阅
     */
    public function getUserSubscriptions()
    {
        return $this->index();
    }

    /**
     * 获取订阅详情
     */
    public function show($id)
    {
        $user = auth()->user();
        
        $subscription = Subscription::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['plan', 'deliveries'])
            ->first();

        if (!$subscription) {
            return $this->error('Subscription not found', 404);
        }

        return $this->success($subscription);
    }

    /**
     * 取消订阅（立即取消）
     */
    public function cancel($id)
    {
        $user = auth()->user();
        
        $subscription = Subscription::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$subscription) {
            return $this->error('Subscription not found', 404);
        }

        if ($subscription->status === Subscription::STATUS_CANCELLED) {
            return $this->error('Subscription already cancelled', 400);
        }

        try {
            // 在Stripe中立即取消订阅
            if ($subscription->stripe_subscription_id && env('STRIPE_SECRET_KEY')) {
                try {
                    \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
                    
                    // 先获取订阅对象，再调用cancel方法
                    $stripeSubscription = \Stripe\Subscription::retrieve($subscription->stripe_subscription_id);
                    $cancelledSubscription = $stripeSubscription->cancel();
                    
                    \Log::info('Stripe subscription cancelled immediately', [
                        'subscription_id' => $id,
                        'stripe_subscription_id' => $subscription->stripe_subscription_id,
                        'stripe_status' => $cancelledSubscription->status,
                    ]);
                    
                } catch (\Exception $stripeError) {
                    \Log::warning('Stripe cancellation failed', [
                        'error' => $stripeError->getMessage(),
                        'subscription_id' => $id
                    ]);
                    // 如果Stripe取消失败，仍然更新本地状态
                }
            }

            // 更新本地订阅状态
            $subscription->status = Subscription::STATUS_CANCELLED;
            $subscription->save();

            // 检查用户是否还有其他活跃订阅，如果没有则取消订阅用户标识
            $activeSubscriptions = Subscription::where('user_id', $user->id)
                ->where('status', Subscription::STATUS_ACTIVE)
                ->count();
            
            if ($activeSubscriptions === 0) {
                $user->is_subscriber = 0;
                $user->save();
            }

            return $this->success([
                'subscription' => $subscription,
            ], 'Subscription cancelled successfully');

        } catch (\Exception $e) {
            \Log::error('Subscription cancellation failed', [
                'error' => $e->getMessage(),
                'subscription_id' => $id,
            ]);
            return $this->error('Failed to cancel subscription: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 暂停订阅
     */
    public function pause($id)
    {
        $user = auth()->user();
        
        $subscription = Subscription::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$subscription) {
            return $this->error('Subscription not found', 404);
        }

        try {
            // 在Stripe中暂停订阅
            if ($subscription->stripe_subscription_id) {
                \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
                \Stripe\Subscription::update(
                    $subscription->stripe_subscription_id,
                    ['pause_collection' => ['behavior' => 'mark_uncollectible']]
                );
            }

            $subscription->update(['status' => Subscription::STATUS_PAUSED]);

            return $this->success($subscription, 'Subscription paused successfully');

        } catch (\Exception $e) {
            \Log::error('Subscription pause failed', [
                'error' => $e->getMessage(),
                'subscription_id' => $id,
            ]);
            return $this->error('Failed to pause subscription: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 恢复订阅
     */
    public function resume($id)
    {
        $user = auth()->user();
        
        $subscription = Subscription::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$subscription) {
            return $this->error('Subscription not found', 404);
        }

        try {
            // 在Stripe中恢复订阅
            if ($subscription->stripe_subscription_id) {
                \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
                \Stripe\Subscription::update(
                    $subscription->stripe_subscription_id,
                    ['pause_collection' => '']
                );
            }

            $subscription->update(['status' => Subscription::STATUS_ACTIVE]);

            return $this->success($subscription, 'Subscription resumed successfully');

        } catch (\Exception $e) {
            \Log::error('Subscription resume failed', [
                'error' => $e->getMessage(),
                'subscription_id' => $id,
            ]);
            return $this->error('Failed to resume subscription: ' . $e->getMessage(), 500);
        }
    }
}

