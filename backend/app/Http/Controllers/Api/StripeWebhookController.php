<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionDelivery;
use App\Models\User;
use App\Models\EmailTask;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class StripeWebhookController extends Controller
{
    /**
     * 处理Stripe Webhook
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = env('STRIPE_WEBHOOK_SECRET');

        if (!$webhookSecret) {
            Log::error('Stripe webhook secret not configured');
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
            Log::error('Stripe webhook invalid payload: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe webhook invalid signature: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('Stripe webhook received', [
            'type' => $event->type,
            'id' => $event->id,
        ]);

        try {
            switch ($event->type) {
                case 'checkout.session.completed':
                    $this->handleCheckoutSessionCompleted($event->data->object);
                    break;

                case 'customer.subscription.created':
                case 'customer.subscription.updated':
                    $this->handleSubscriptionChange($event->data->object);
                    break;

                case 'customer.subscription.deleted':
                    $this->handleSubscriptionDeleted($event->data->object);
                    break;

                case 'invoice.payment_succeeded':
                    $this->handleInvoicePaymentSucceeded($event->data->object);
                    break;

                case 'invoice.payment_failed':
                    $this->handleInvoicePaymentFailed($event->data->object);
                    break;

                default:
                    Log::info('Unhandled webhook event type: ' . $event->type);
            }

            return response()->json(['received' => true]);

        } catch (\Exception $e) {
            Log::error('Stripe webhook processing failed', [
                'error' => $e->getMessage(),
                'event_type' => $event->type,
            ]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * 处理Checkout Session完成事件（订阅支付成功或普通订单支付）
     */
    private function handleCheckoutSessionCompleted($session)
    {
        DB::beginTransaction();
        try {
            Log::info('Processing checkout.session.completed', [
                'session_id' => $session->id,
                'customer' => $session->customer,
                'subscription' => $session->subscription,
                'mode' => $session->mode,
            ]);

            // 判断是否为订阅支付（mode='subscription' 且有 subscription ID）
            if ($session->mode !== 'subscription' || empty($session->subscription)) {
                // 这是普通订单支付，不在这里处理（由PaymentController处理）
                Log::info('Not a subscription checkout, skipping', [
                    'session_id' => $session->id,
                    'mode' => $session->mode,
                ]);
                DB::commit();
                return;
            }

            // 从metadata获取用户ID和计划ID
            $userId = $session->metadata->user_id ?? null;
            $planId = $session->metadata->plan_id ?? null;

            if (!$userId || !$planId) {
                Log::error('Missing subscription metadata in checkout session', [
                    'session_id' => $session->id,
                    'metadata' => $session->metadata,
                ]);
                DB::rollBack();
                return;
            }

            // 查找用户
            $user = User::find($userId);
            if (!$user) {
                Log::error('User not found', ['user_id' => $userId]);
                DB::rollBack();
                return;
            }

            // 查找订阅计划
            $plan = \App\Models\SubscriptionPlan::find($planId);
            if (!$plan) {
                Log::error('Subscription plan not found', ['plan_id' => $planId]);
                DB::rollBack();
                return;
            }

            // 获取Stripe订阅ID和客户ID
            $stripeSubscriptionId = $session->subscription;
            $stripeCustomerId = $session->customer;

            // 检查订阅是否已存在
            $existingSubscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();
            if ($existingSubscription) {
                Log::info('Subscription already exists', [
                    'subscription_id' => $existingSubscription->id,
                ]);
                DB::rollBack();
                return;
            }

            // 获取Stripe订阅详情以获取周期信息
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            $stripeSubscription = \Stripe\Subscription::retrieve($stripeSubscriptionId);

            // 创建订阅记录
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'stripe_subscription_id' => $stripeSubscriptionId,
                'stripe_customer_id' => $stripeCustomerId,
                'plan_name' => $plan->name,
                'plan_type' => $plan->plan_type,
                'bottles_per_delivery' => $plan->bottles_per_delivery,
                'price' => $plan->price,
                'status' => Subscription::STATUS_ACTIVE,
                'current_period_start' => Carbon::createFromTimestamp($stripeSubscription->current_period_start),
                'current_period_end' => Carbon::createFromTimestamp($stripeSubscription->current_period_end),
                'next_delivery_date' => null,
            ]);

            Log::info('Subscription created', [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);

            // 更新用户为订阅用户
            $user->is_subscriber = 1;
            $user->save();

            // 创建订阅订单
            $this->createSubscriptionOrder($user, $plan, $subscription);

            DB::commit();

            Log::info('Checkout session processed successfully', [
                'session_id' => $session->id,
                'subscription_id' => $subscription->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process checkout session', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 处理订阅创建/更新
     */
    private function handleSubscriptionChange($stripeSubscription)
    {
        DB::beginTransaction();
        try {
            $customerId = $stripeSubscription->customer;
            $subscriptionId = $stripeSubscription->id;

            Log::info('Processing subscription change', [
                'stripe_subscription_id' => $subscriptionId,
                'customer_id' => $customerId,
                'status' => $stripeSubscription->status,
            ]);

            // 查找用户 - 先通过 stripe_customer_id 查找
            $user = User::where('stripe_customer_id', $customerId)->first();
            
            // 如果没找到，尝试通过 Stripe 获取客户邮箱来匹配
            if (!$user) {
                try {
                    \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
                    $stripeCustomer = \Stripe\Customer::retrieve($customerId);
                    
                    if ($stripeCustomer && $stripeCustomer->email) {
                        $user = User::where('email', $stripeCustomer->email)->first();
                        
                        // 如果找到用户，更新其 stripe_customer_id
                        if ($user) {
                            $user->stripe_customer_id = $customerId;
                            $user->save();
                            Log::info('User found by email and stripe_customer_id updated', [
                                'user_id' => $user->id,
                                'email' => $stripeCustomer->email,
                                'customer_id' => $customerId,
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to retrieve Stripe customer', [
                        'customer_id' => $customerId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            if (!$user) {
                Log::warning('User not found for customer', [
                    'customer_id' => $customerId,
                    'subscription_id' => $subscriptionId,
                ]);
                DB::rollBack();
                return;
            }

            // 获取订阅计划信息（通过Stripe Price ID查找）
            $priceId = $stripeSubscription->items->data[0]->price->id;
            $plan = \App\Models\SubscriptionPlan::where('stripe_price_id', $priceId)
                ->where('status', \App\Models\SubscriptionPlan::STATUS_ACTIVE)
                ->first();

            if (!$plan) {
                Log::warning('Subscription plan not found for price', ['price_id' => $priceId]);
                DB::rollBack();
                return;
            }

            // 计算下次扣款日期
            $currentPeriodEnd = Carbon::createFromTimestamp($stripeSubscription->current_period_end);

            // 创建或更新订阅记录
            $subscription = Subscription::updateOrCreate(
                ['stripe_subscription_id' => $subscriptionId],
                [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id, // 使用订阅计划ID
                    'stripe_customer_id' => $customerId,
                    'plan_name' => $plan->name,
                    'plan_type' => $plan->plan_type,
                    'bottles_per_delivery' => $plan->bottles_per_delivery,
                    'price' => $plan->price,
                    'status' => $stripeSubscription->status,
                    'current_period_start' => Carbon::createFromTimestamp($stripeSubscription->current_period_start),
                    'current_period_end' => $currentPeriodEnd,
                    'next_delivery_date' => null, // 订阅不涉及发货，设为null
                ]
            );

            // 如果是首次创建订阅，创建订单并更新用户状态
            if ($subscription->wasRecentlyCreated) {
                // 更新用户为订阅用户
                $user->is_subscriber = 1;
                $user->save();
                
                $this->createSubscriptionOrder($user, $plan, $subscription);
            }

            // 如果订阅状态变为活跃，确保用户标记为订阅用户
            if ($stripeSubscription->status === 'active' && !$user->is_subscriber) {
                $user->is_subscriber = 1;
                $user->save();
            }

            DB::commit();

            Log::info('Subscription processed', [
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $subscriptionId,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process subscription change', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 创建订阅订单
     */
    private function createSubscriptionOrder($user, $plan, $subscription)
    {
        try {
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
                'pay_status' => 1, // 已支付
                'status' => 1, // 待发货
                'is_subscription' => 1, // 标记为订阅订单
                'subscription_id' => $subscription->id,
                'shipping_name' => $address->name ?? $user->username,
                'shipping_email' => $address->email ?? $user->email,
                'shipping_phone' => $address->phone ?? $user->phone,
                'shipping_address' => $address->address ?? 'Pending address',
                'shipping_city' => $address->city ?? null,
                'shipping_postal_code' => $address->postal_code ?? null,
                'payment_method' => 'stripe_subscription',
                'payment_no' => $subscription->stripe_subscription_id,
                'paid_at' => Carbon::now(),
            ]);

            // 创建订单项（订阅计划作为订单项）
            \App\Models\OrderItem::create([
                'order_id' => $order->id,
                'product_id' => 0, // 订阅订单没有关联商品
                'product_name' => $plan->name . ' - ' . $plan->description,
                'product_image' => '', // 订阅计划没有图片
                'price' => $plan->price,
                'quantity' => 1,
                'total_amount' => $plan->price,
            ]);

            Log::info('Subscription order created', [
                'order_id' => $order->id,
                'order_no' => $orderNo,
                'subscription_id' => $subscription->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create subscription order', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscription->id,
            ]);
            throw $e;
        }
    }

    /**
     * 处理订阅删除（来自Stripe的取消通知）
     */
    private function handleSubscriptionDeleted($stripeSubscription)
    {
        try {
            Log::info('Processing subscription.deleted webhook', [
                'stripe_subscription_id' => $stripeSubscription->id,
                'customer_id' => $stripeSubscription->customer ?? null,
                'status' => $stripeSubscription->status ?? null,
            ]);

            $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

            if (!$subscription) {
                // 尝试通过 customer_id 查找
                $customerId = $stripeSubscription->customer;
                Log::warning('Subscription not found by stripe_subscription_id, trying customer_id', [
                    'stripe_subscription_id' => $stripeSubscription->id,
                    'customer_id' => $customerId,
                ]);
                
                // 也许订阅记录是通过 customer_id 关联的
                $subscription = Subscription::where('stripe_customer_id', $customerId)
                    ->where('status', Subscription::STATUS_ACTIVE)
                    ->first();
                    
                if (!$subscription) {
                    Log::error('Subscription not found in database', [
                        'stripe_subscription_id' => $stripeSubscription->id,
                        'customer_id' => $customerId,
                    ]);
                    return;
                }
            }

            // 更新订阅状态
            $subscription->status = Subscription::STATUS_CANCELLED;
            $subscription->save();

            Log::info('Subscription status updated to cancelled', [
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $stripeSubscription->id,
            ]);

            // 检查用户是否还有其他活跃订阅
            $user = $subscription->user;
            if ($user) {
                $activeSubscriptions = Subscription::where('user_id', $user->id)
                    ->where('status', Subscription::STATUS_ACTIVE)
                    ->count();
                
                if ($activeSubscriptions === 0) {
                    $user->is_subscriber = 0;
                    $user->save();
                    Log::info('User is_subscriber set to 0', ['user_id' => $user->id]);
                }
                
                // 发送订阅取消通知邮件
                $this->sendSubscriptionCancelledEmail($subscription);
            }

            Log::info('Subscription cancelled successfully via webhook', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process subscription deletion', [
                'error' => $e->getMessage(),
                'stripe_subscription_id' => $stripeSubscription->id ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 发送订阅取消邮件
     */
    private function sendSubscriptionCancelledEmail($subscription)
    {
        try {
            $user = $subscription->user;
            if (!$user || !$user->email) return;

            $subject = 'Subscription Cancelled';
            $content = "
                <h1>Subscription Cancelled</h1>
                <p>Hi {$user->username},</p>
                <p>Your subscription to <strong>{$subscription->plan_name}</strong> has been cancelled.</p>
                <p>If this was a mistake or you'd like to resubscribe, you can do so anytime from your account.</p>
                <p><a href=\"" . env('APP_FRONTEND_URL') . "/user-center/subscriptions\">Manage Subscriptions</a></p>
                <p>Thank you for being a member of The Sauce Club!</p>
                <p>Best regards,<br>The Sauce Club Team</p>
            ";

            MailService::sendHtmlMail($user->email, $subject, $content);
            Log::info('Subscription cancelled email sent', ['user_id' => $user->id]);

        } catch (\Exception $e) {
            Log::error('Failed to send subscription cancelled email', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 处理支付成功（包括首次支付和续费）
     */
    private function handleInvoicePaymentSucceeded($invoice)
    {
        DB::beginTransaction();
        try {
            $stripeSubscriptionId = $invoice->subscription;
            
            if (!$stripeSubscriptionId) {
                Log::info('Invoice is not for subscription, skipping', ['invoice_id' => $invoice->id]);
                DB::commit();
                return;
            }

            $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();

            if (!$subscription) {
                Log::warning('Subscription not found for invoice', ['subscription_id' => $stripeSubscriptionId]);
                DB::rollBack();
                return;
            }

            // 获取用户和计划
            $user = $subscription->user;
            $plan = $subscription->plan;

            if (!$user || !$plan) {
                Log::error('User or plan not found for subscription', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'plan_id' => $subscription->plan_id,
                ]);
                DB::rollBack();
                return;
            }

            // 判断是否为续费（通过检查billing_reason）
            // billing_reason 可能是: subscription_create, subscription_cycle, subscription_update 等
            $billingReason = $invoice->billing_reason ?? 'unknown';
            
            Log::info('Processing invoice payment', [
                'invoice_id' => $invoice->id,
                'billing_reason' => $billingReason,
                'subscription_id' => $subscription->id,
            ]);

            // 续费订单：billing_reason 为 subscription_cycle 或 subscription_update
            // 首次订单在 checkout.session.completed 中已创建，这里不重复创建
            if ($billingReason === 'subscription_cycle') {
                // 这是续费，创建续费订单
                $this->createSubscriptionOrder($user, $plan, $subscription);
                Log::info('Renewal order created', [
                    'subscription_id' => $subscription->id,
                    'billing_reason' => $billingReason,
                ]);
            }

            // 更新订阅状态为活跃（如果之前是past_due）
            if ($subscription->status !== Subscription::STATUS_ACTIVE) {
                $subscription->status = Subscription::STATUS_ACTIVE;
                $subscription->save();
            }

            // 更新订阅周期信息
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            $stripeSubscription = \Stripe\Subscription::retrieve($stripeSubscriptionId);
            
            $subscription->current_period_start = Carbon::createFromTimestamp($stripeSubscription->current_period_start);
            $subscription->current_period_end = Carbon::createFromTimestamp($stripeSubscription->current_period_end);
            $subscription->save();

            DB::commit();

            // 发送支付成功邮件
            $this->sendPaymentSuccessEmail($subscription, $invoice);

            Log::info('Invoice payment succeeded processed', [
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice->id,
                'billing_reason' => $billingReason,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process invoice payment succeeded', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 处理支付失败
     */
    private function handleInvoicePaymentFailed($invoice)
    {
        try {
            $subscriptionId = $invoice->subscription;
            $subscription = Subscription::where('stripe_subscription_id', $subscriptionId)->first();

            if (!$subscription) {
                return;
            }

            // 更新订阅状态
            $subscription->update(['status' => Subscription::STATUS_PAST_DUE]);

            // 发送支付失败邮件
            $this->sendPaymentFailedEmail($subscription, $invoice);

            Log::warning('Invoice payment failed', [
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process invoice payment failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 计算下次发货日期
     */
    private function calculateNextDeliveryDate($deliveryDay)
    {
        $today = Carbon::now();
        $currentDay = $today->day;

        if ($currentDay < $deliveryDay) {
            // 本月还没到发货日
            return $today->copy()->day($deliveryDay)->format('Y-m-d');
        } else {
            // 本月已过，下个月发货
            return $today->copy()->addMonth()->day($deliveryDay)->format('Y-m-d');
        }
    }

    /**
     * 发送支付成功邮件
     */
    private function sendPaymentSuccessEmail($subscription, $invoice)
    {
        try {
            $user = $subscription->user;
            if (!$user || !$user->email) return;

            $subject = 'Subscription Payment Successful';
            $content = "
                <h1>Payment Successful!</h1>
                <p>Hi {$user->username},</p>
                <p>Your subscription payment of \${$subscription->price} has been processed successfully.</p>
                <p>Plan: {$subscription->plan_name}</p>
                <p>Next delivery date: {$subscription->next_delivery_date}</p>
                <p>Thank you for being a member of The Sauce Club!</p>
                <p>Best regards,<br>The Sauce Club Team</p>
            ";

            MailService::sendHtmlMail($user->email, $subject, $content);

        } catch (\Exception $e) {
            Log::error('Failed to send payment success email', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 发送支付失败邮件
     */
    private function sendPaymentFailedEmail($subscription, $invoice)
    {
        try {
            $user = $subscription->user;
            if (!$user || !$user->email) return;

            $subject = 'Subscription Payment Failed';
            $content = "
                <h1>Payment Failed</h1>
                <p>Hi {$user->username},</p>
                <p>We were unable to process your subscription payment for {$subscription->plan_name}.</p>
                <p>Please update your payment method to avoid service interruption.</p>
                <p><a href=\"" . env('APP_FRONTEND_URL') . "/user-center/subscriptions\">Update Payment Method</a></p>
                <p>Best regards,<br>The Sauce Club Team</p>
            ";

            MailService::sendHtmlMail($user->email, $subject, $content);

        } catch (\Exception $e) {
            Log::error('Failed to send payment failed email', ['error' => $e->getMessage()]);
        }
    }
}

