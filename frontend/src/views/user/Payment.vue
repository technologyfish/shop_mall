<template>
  <div class="payment-page">
    <div class="container">
      <h1 class="page-title">Payment</h1>

      <div class="payment-layout" v-if="order">
        <!-- Left: Order Summary -->
        <div class="order-summary">
          <div class="section-card">
            <h2>Order Summary</h2>
            
            <div class="order-info">
              <div class="info-row">
                <span class="label">Order No:</span>
                <span class="value">{{ order.order_no }}</span>
              </div>
              <div class="info-row">
                <span class="label">Order Time:</span>
                <span class="value">{{ formatDate(order.created_at) }}</span>
              </div>
            </div>

            <div class="order-items">
              <h3>Order Items</h3>
              <div v-for="item in order.items" :key="item.id" class="item-row">
                <img :src="getImageUrl(item.product_image)" :alt="item.product_name" class="item-img" />
                <div class="item-info">
                  <p class="item-name">{{ item.product_name }}</p>
                  <p class="item-meta">Qty: {{ item.quantity }} × £{{ parseFloat(item.price).toFixed(2) }}</p>
                </div>
                <div class="item-total">£{{ (item.price * item.quantity).toFixed(2) }}</div>
              </div>
            </div>

            <div class="shipping-info">
              <h3>Shipping Address</h3>
              <p><strong>{{ order.shipping_name }}</strong> {{ order.shipping_phone }}</p>
              <p>{{ order.shipping_address }}</p>
            </div>

            <div class="amount-summary">
              <div class="amount-row">
                <span>Subtotal</span>
                <span>£{{ parseFloat(order.total_amount).toFixed(2) }}</span>
              </div>
              <div class="amount-row" v-if="order.shipping_fee > 0">
                <span>Shipping</span>
                <span>£{{ parseFloat(order.shipping_fee).toFixed(2) }}</span>
              </div>
              <div class="amount-row" v-else-if="order.total_amount > 0">
                <span>Shipping</span>
                <span style="color: #67c23a;">Free</span>
              </div>
              <div class="amount-row discount" v-if="order.discount_amount > 0">
                <span>{{ order.promotion_id === 1 ? 'First Order Discount' : 'Discount' }}</span>
                <span class="discount-price">-£{{ parseFloat(order.discount_amount).toFixed(2) }}</span>
              </div>
              <div class="amount-row total">
                <span>Total Amount</span>
                <span class="total-price">£{{ parseFloat(order.pay_amount).toFixed(2) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Payment Method -->
        <div class="payment-section">
          <div class="section-card">
            <h2>Payment Method</h2>
            
            <el-radio-group v-model="paymentMethod" class="payment-methods">
              <el-radio label="bank_card" border>
                <div class="payment-option">
                  <el-icon size="24"><CreditCard /></el-icon>
                  <div class="option-info">
                    <h4>Bank Card Payment</h4>
                    <p>Secure payment via third-party gateway</p>
                  </div>
                </div>
              </el-radio>
              
              <!-- 可以添加更多支付方式 -->
              <!-- <el-radio label="alipay" border>
                <div class="payment-option">
                  <el-icon size="24"><Wallet /></el-icon>
                  <div class="option-info">
                    <h4>Alipay</h4>
                    <p>Pay with Alipay</p>
                  </div>
                </div>
              </el-radio> -->
            </el-radio-group>

            <div class="payment-notice">
              <el-icon><InfoFilled /></el-icon>
              <p>You will be redirected to a secure payment page to complete your transaction.</p>
            </div>

            <el-button 
              type="primary" 
              size="large" 
              class="btn-pay" 
              :loading="paying"
              @click="handlePay"
              :disabled="!paymentMethod"
            >
              {{ paying ? 'Processing...' : `Pay £${parseFloat(order.pay_amount).toFixed(2)}` }}
            </el-button>

            <div class="back-link">
              <el-button link @click="$router.push('/orders')">
                <el-icon><ArrowLeft /></el-icon>
                Back to Orders
              </el-button>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="loading-state">
        <el-skeleton :rows="8" animated />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import message from '@/utils/message'
import { CreditCard, InfoFilled, ArrowLeft } from '@element-plus/icons-vue'
import { getOrder } from '@/api/order'
import { createPayment } from '@/api/payment'

const route = useRoute()
const router = useRouter()

const order = ref(null)
const paymentMethod = ref('bank_card')
const paying = ref(false)

// 获取订单详情
const fetchOrder = async () => {
  try {
    const orderId = route.params.id || route.query.order_id
    if (!orderId) {
      message.error('Order ID not found')
      router.push('/orders')
      return
    }

    const res = await getOrder(orderId)
    order.value = res.data.data

    // 检查订单状态
    if (order.value.pay_status === 1) {
      message.warning('This order has been paid')
      router.push(`/order/${orderId}`)
      return
    }
  } catch (error) {
    message.error('Failed to load order')
    router.push('/orders')
  }
}

// 格式化日期
const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// 获取图片URL
const getImageUrl = (imagePath) => {
  if (!imagePath) return 'https://via.placeholder.com/80'
  if (imagePath.startsWith('http')) return imagePath
  return `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'}${imagePath}`
}

// 发起支付
const handlePay = async () => {
  if (!paymentMethod.value) {
    message.warning('Please select a payment method')
    return
  }

  paying.value = true
  try {
    const res = await createPayment({
      order_no: order.value.order_no,
      payment_method: paymentMethod.value,
      return_url: `${window.location.origin}/payment/result`,
      callback_url: `http://localhost:8000/api/payments/callback`
    })

    const paymentData = res.data.data

    // 如果返回支付URL，跳转到第三方支付平台
    if (paymentData.payment_url) {
      // 直接跳转，不显示弹窗
      window.location.href = paymentData.payment_url
    } else {
      message.error('Payment URL not returned')
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to initiate payment')
  } finally {
    paying.value = false
  }
}

onMounted(() => {
  fetchOrder()
})
</script>

<style scoped lang="scss">
.payment-page {
  min-height: 100vh;
  background: var(--primary-lighter);
  padding: 40px 0 60px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.page-title {
  color: var(--text-white);
  font-size: 28px;
  font-weight: bold;
  margin-bottom: 30px;
  font-family: 'Courier New', serif;
}

.payment-layout {
  display: grid;
  grid-template-columns: 1.5fr 1fr;
  gap: 30px;

  @media (max-width: 968px) {
    grid-template-columns: 1fr;
  }
}

.section-card {
  background: white;
  border-radius: 12px;
  padding: 30px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);

  h2 {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
  }

  h3 {
    font-size: 16px;
    font-weight: 600;
    margin: 20px 0 15px;
  }
}

.order-info {
  margin-bottom: 20px;

  .info-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    font-size: 14px;

    .label {
      color: #666;
    }

    .value {
      font-weight: 500;
    }
  }
}

.order-items {
  .item-row {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #f5f5f5;

    .item-img {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 8px;
    }

    .item-info {
      flex: 1;

      .item-name {
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 5px;
      }

      .item-meta {
        font-size: 12px;
        color: #999;
      }
    }

    .item-total {
      font-weight: bold;
      color: var(--primary-color);
    }
  }
}

.shipping-info {
  padding: 15px;
  background: #f9f9f9;
  border-radius: 8px;
  margin-top: 20px;

  p {
    margin: 5px 0;
    font-size: 14px;
    color: #666;
  }
}

.amount-summary {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 2px solid #f0f0f0;

  .amount-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 14px;
    
    &.discount {
      color: #67c23a;
      
      .discount-price {
        font-weight: 500;
      }
    }

    &.total {
      font-size: 18px;
      font-weight: bold;
      padding-top: 15px;
      margin-top: 10px;
      border-top: 1px solid #eee;

      .total-price {
        color: var(--primary-color);
        font-size: 24px;
      }
    }
  }
}

.payment-methods {
  display: flex;
  flex-direction: column;
  gap: 15px;
  width: 100%;

  :deep(.el-radio) {
    width: 100%;
    margin: 0;
    padding: 20px;
    height: auto;
    
    &.is-checked {
      border-color: var(--primary-color);
    }
  }

  .payment-option {
    display: flex;
    align-items: center;
    gap: 15px;

    .el-icon {
      color: var(--primary-color);
    }

    .option-info {
      h4 {
        margin: 0 0 5px 0;
        font-size: 16px;
        font-weight: 600;
      }

      p {
        margin: 0;
        font-size: 13px;
        color: #999;
      }
    }
  }
}

.payment-notice {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 15px;
  background: #fffbf0;
  border: 1px solid #ffe7ba;
  border-radius: 8px;
  margin: 20px 0;

  .el-icon {
    color: #faad14;
    margin-top: 2px;
  }

  p {
    margin: 0;
    font-size: 13px;
    color: #666;
    line-height: 1.5;
  }
}

.btn-pay {
  width: 100%;
  height: 50px;
  font-size: 16px;
  font-weight: 600;
  background: var(--primary-color);
  border: none;

  &:hover:not(:disabled) {
    background: var(--primary-dark);
  }
}

.back-link {
  text-align: center;
  margin-top: 15px;
}

.loading-state {
  background: white;
  border-radius: 12px;
  padding: 30px;
}
</style>


