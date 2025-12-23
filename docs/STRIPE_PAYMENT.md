# Stripe 支付与订阅 技术文档

## 一、概述

本项目使用 Stripe 作为支付网关，支持：
- **一次性支付**：商品购买
- **订阅支付**：周期性订阅计划（月付/季付/年付）

---

## 二、配置说明

### 2.1 获取 API 密钥

1. 登录 [Stripe Dashboard](https://dashboard.stripe.com)
2. 进入 **Developers** -> **API keys**
3. 复制密钥：
   - `Secret key`：后端使用（sk_test_xxx 或 sk_live_xxx）
   - `Publishable key`：前端使用（pk_test_xxx 或 pk_live_xxx）

### 2.2 后端配置

```env
# .env 文件
STRIPE_SECRET_KEY=sk_live_xxxxxxxxxxxxxxxx
STRIPE_PUBLISHABLE_KEY=pk_live_xxxxxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxx
```

---

## 三、一次性支付流程

### 3.1 流程图

```
用户下单 → 创建订单 → 创建 Stripe Checkout Session → 跳转 Stripe 支付页
    ↓
支付成功 → Stripe 回调 success_url → 前端验证支付状态
    ↓
Webhook: checkout.session.completed → 后端更新订单状态
```

### 3.2 核心代码

**创建支付会话** (`PaymentController.php`)

```php
$session = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],
    'line_items' => [
        [
            'price_data' => [
                'currency' => 'usd',
                'product_data' => ['name' => '订单: ' . $order->order_no],
                'unit_amount' => $order->pay_amount * 100, // 分为单位
            ],
            'quantity' => 1,
        ],
        // 运费单独列出
        [
            'price_data' => [
                'currency' => 'usd',
                'product_data' => ['name' => 'Shipping Fee'],
                'unit_amount' => $order->shipping_fee * 100,
            ],
            'quantity' => 1,
        ],
    ],
    'mode' => 'payment',
    'success_url' => $frontendUrl . '/payment/result?order_no=' . $order->order_no,
    'cancel_url' => $frontendUrl . '/payment/cancel',
    'metadata' => [
        'user_id' => $user->id,
        'order_id' => $order->id,
        'order_no' => $order->order_no,
    ],
]);
```

---

## 四、订阅支付流程

### 4.1 流程图

```
用户选择订阅计划 → 创建 Stripe Checkout Session (mode=subscription)
    ↓
跳转 Stripe 支付页 → 支付成功 → 回调 /subscription/success
    ↓
前端调用 /api/subscriptions/verify → 后端验证并创建本地订阅记录
    ↓
Stripe 自动：
  - 记录订阅周期
  - 到期自动扣款
  - 发送 Webhook 通知
```

### 4.2 核心代码

**创建订阅会话** (`SubscriptionController.php`)

```php
// 创建或获取 Stripe Customer
if (!$user->stripe_customer_id) {
    $customer = \Stripe\Customer::create([
        'email' => $user->email,
        'name' => $user->username,
        'metadata' => ['user_id' => $user->id],
    ]);
    $user->stripe_customer_id = $customer->id;
    $user->save();
}

// 创建订阅 Checkout Session
$session = \Stripe\Checkout\Session::create([
    'customer' => $user->stripe_customer_id,
    'payment_method_types' => ['card'],
    'line_items' => [[
        'price' => $plan->stripe_price_id,  // Stripe 后台创建的 Price ID
        'quantity' => 1,
    ]],
    'mode' => 'subscription',
    'success_url' => $frontendUrl . '/subscription/success?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => $frontendUrl . '/subscription/cancelled',
    'metadata' => [
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ],
]);
```

**验证订阅** (`SubscriptionController@verify`)

```php
// 从 Stripe 获取 Session 详情
$session = \Stripe\Checkout\Session::retrieve($sessionId);

// 验证支付状态
if ($session->payment_status !== 'paid') {
    return $this->error('Payment not completed');
}

// 获取 Stripe 订阅详情
$stripeSubscription = \Stripe\Subscription::retrieve($session->subscription);

// 创建本地订阅记录
$subscription = Subscription::create([
    'user_id' => $user->id,
    'plan_id' => $plan->id,
    'stripe_subscription_id' => $stripeSubscription->id,
    'stripe_customer_id' => $session->customer,
    'status' => 'active',
    'current_period_start' => Carbon::createFromTimestamp($stripeSubscription->current_period_start),
    'current_period_end' => Carbon::createFromTimestamp($stripeSubscription->current_period_end),
]);

// 更新用户订阅状态
$user->is_subscriber = 1;
$user->save();
```

---

## 五、Webhook 配置

### 5.1 配置步骤

1. 进入 Stripe Dashboard -> **Developers** -> **Webhooks**
2. 点击 **Add endpoint**
3. 填写：
   - URL: `https://api.your-domain.com/api/stripe/webhook`
   - 选择事件：
     - `checkout.session.completed`
     - `customer.subscription.created`
     - `customer.subscription.updated`
     - `customer.subscription.deleted`
     - `invoice.payment_succeeded`
     - `invoice.payment_failed`
4. 复制 **Signing secret** 到 `.env` 的 `STRIPE_WEBHOOK_SECRET`

### 5.2 Webhook 处理

**文件**: `StripeWebhookController.php`

| 事件 | 处理逻辑 |
|------|----------|
| `checkout.session.completed` | 订单/订阅首次支付成功 |
| `customer.subscription.updated` | 订阅状态变更 |
| `customer.subscription.deleted` | 订阅被取消 |
| `invoice.payment_succeeded` | 支付成功（含续费），创建续费订单 |
| `invoice.payment_failed` | 支付失败，更新订阅状态为 past_due |

### 5.3 续费订单自动生成

当 `invoice.payment_succeeded` 且 `billing_reason === 'subscription_cycle'` 时：

```php
// 这是续费，创建续费订单
if ($billingReason === 'subscription_cycle') {
    $this->createSubscriptionOrder($user, $plan, $subscription);
}
```

---

## 六、取消订阅

### 6.1 用户取消

**文件**: `SubscriptionController@cancel`

```php
// 从 Stripe 取消订阅
$stripeSubscription = \Stripe\Subscription::retrieve($subscription->stripe_subscription_id);
$stripeSubscription->cancel();

// 更新本地状态
$subscription->status = 'cancelled';
$subscription->save();

// 更新用户订阅标识
if (没有其他活跃订阅) {
    $user->is_subscriber = 0;
    $user->save();
}
```

---

## 七、Stripe 后台产品配置

### 7.1 创建订阅产品

1. 进入 **Products** -> **Add product**
2. 填写产品名称和描述
3. 添加价格：
   - **Recurring**（周期性）
   - 设置金额和周期（Monthly/Yearly 等）
4. 复制 **Price ID**（如 `price_1xxxxx`）

### 7.2 配置到后台

在后台管理 -> 订阅计划管理：
- 填入对应的 `stripe_price_id`
- 确保金额与 Stripe 后台一致

---

## 八、测试

### 8.1 测试卡号

| 卡号 | 说明 |
|------|------|
| 4242 4242 4242 4242 | 成功支付 |
| 4000 0000 0000 0002 | 卡被拒绝 |
| 4000 0000 0000 3220 | 需要 3D 验证 |

### 8.2 测试 Webhook

使用 Stripe CLI：

```bash
# 安装
# Windows: scoop install stripe

# 登录
stripe login

# 转发 webhook 到本地
stripe listen --forward-to localhost:8000/api/stripe/webhook

# 触发测试事件
stripe trigger checkout.session.completed
```

---

## 九、常见问题

### Q: Webhook 收不到？
1. 确认 URL 可公网访问
2. 检查 `STRIPE_WEBHOOK_SECRET` 配置
3. 查看 Stripe Dashboard 的 Webhook 日志

### Q: 订阅创建成功但本地没记录？
1. 确认 Webhook 已配置
2. 检查 `handleCheckoutSessionCompleted` 日志
3. 前端确认调用了 `/api/subscriptions/verify`

### Q: 续费没生成订单？
1. 确认 `invoice.payment_succeeded` Webhook 已配置
2. 检查日志中的 `billing_reason`



