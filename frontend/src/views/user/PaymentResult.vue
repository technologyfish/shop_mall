<template>
  <div class="payment-result-page">
    <div class="container">
      <div class="result-card">
        <!-- 支付成功 -->
        <div v-if="status === 'success'" class="result-content success">
          <el-icon class="result-icon" :size="80"><CircleCheck /></el-icon>
          <h1>Payment Successful!</h1>
          <p class="desc">Your order has been confirmed and will be processed soon.</p>
          
          <div class="order-info">
            <p><strong>Order No:</strong> {{ orderNo }}</p>
            <p><strong>Amount Paid:</strong> ¥{{ amount }}</p>
          </div>

          <div class="actions">
            <el-button type="primary" size="large" @click="goToOrderDetail">
              View Orders
            </el-button>
            <el-button size="large" @click="$router.push('/')">
              Continue Shopping
            </el-button>
          </div>
        </div>

        <!-- 支付失败 -->
        <div v-else-if="status === 'failed'" class="result-content failed">
          <el-icon class="result-icon" :size="80"><CircleClose /></el-icon>
          <h1>Payment Failed</h1>
          <p class="desc">{{ errorMessage || 'Your payment could not be processed. Please try again.' }}</p>
          
          <div class="actions">
            <el-button type="primary" size="large" @click="retryPayment">
              Try Again
            </el-button>
            <el-button size="large" @click="$router.push('/orders')">
              Back to Orders
            </el-button>
          </div>
        </div>

        <!-- 支付处理中 -->
        <div v-else class="result-content processing">
          <el-icon class="result-icon spinning" :size="80"><Loading /></el-icon>
          <h1>Processing Payment...</h1>
          <p class="desc">Please wait while we confirm your payment.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
// ElMessage removed - using message modal instead
import { CircleCheck, CircleClose, Loading } from '@element-plus/icons-vue'
import { getPaymentStatus } from '@/api/payment'

const route = useRoute()
const router = useRouter()

const status = ref('processing') // success, failed, processing
const orderNo = ref('')
const amount = ref('')
const orderId = ref('')
const errorMessage = ref('')

// 检查支付状态
const checkPaymentStatus = async () => {
  try {
    // 从URL参数获取订单号
    orderNo.value = route.query.order_no || route.query.out_trade_no
    const paymentStatus = route.query.status
    const sessionId = route.query.session_id
    
    if (!orderNo.value) {
      status.value = 'failed'
      errorMessage.value = 'Order number not found'
      return
    }
    
    // 如果有明确的状态参数（从支付网关返回）
    if (paymentStatus === 'success') {
      // 等待一下让webhook先处理
      await new Promise(resolve => setTimeout(resolve, 1000))
    } else if (paymentStatus === 'failed') {
      status.value = 'failed'
      errorMessage.value = 'Payment was cancelled or failed'
      return
    }

    // 查询支付状态，带上session_id用于验证
    const params = sessionId ? { session_id: sessionId } : {}
    const res = await getPaymentStatus(orderNo.value, params)
    const data = res.data.data

    orderId.value = data.order_id
    amount.value = data.amount
    
    if (data.pay_status === 1 || data.status === 'paid') {
      status.value = 'success'
      // 清空轮询计数
      window.paymentCheckCount = 0
    } else if (data.status === 'failed') {
      status.value = 'failed'
      errorMessage.value = data.error_message || 'Payment failed'
    } else {
      // 如果还在处理中，轮询查询（最多10次）
      if (!window.paymentCheckCount) window.paymentCheckCount = 0
      window.paymentCheckCount++
      
      if (window.paymentCheckCount < 10) {
        setTimeout(checkPaymentStatus, 2000)
      } else {
        status.value = 'failed'
        errorMessage.value = 'Payment verification timeout. Please check your order status.'
      }
    }
  } catch (error) {
    status.value = 'failed'
    errorMessage.value = error.response?.data?.message || 'Failed to verify payment status'
  }
}

// 查看订单详情
const goToOrderDetail = () => {
  router.push('/user-center/orders')
}

// 重试支付
const retryPayment = () => {
  if (orderId.value) {
    router.push(`/payment/${orderId.value}`)
  } else {
    router.push('/orders')
  }
}

onMounted(() => {
  checkPaymentStatus()
})
</script>

<style scoped lang="scss">
.payment-result-page {
  min-height: 100vh;
  background: var(--primary-lighter);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
}

.container {
  width: 100%;
  max-width: 600px;
}

.result-card {
  background: white;
  border-radius: 20px;
  padding: 60px 40px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.result-content {
  text-align: center;

  .result-icon {
    margin-bottom: 30px;

    &.spinning {
      animation: spin 1s linear infinite;
    }
  }

  h1 {
    font-size: 32px;
    margin-bottom: 15px;
  }

  .desc {
    font-size: 16px;
    color: #666;
    margin-bottom: 30px;
    line-height: 1.6;
  }

  &.success {
    .result-icon {
      color: var(--$accent-color);
    }

    h1 {
      color: var(--$accent-color);
    }
  }

  &.failed {
    .result-icon {
      color: #ff4d4f;
    }

    h1 {
      color: #ff4d4f;
    }
  }

  &.processing {
    .result-icon {
      color: var(--primary-lighter);
    }

    h1 {
      color: var(--primary-lighter);
    }
  }
}

.order-info {
  background: #f5f5f5;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 30px;
  text-align: left;

  p {
    margin: 8px 0;
    font-size: 15px;
    color: #333;

    strong {
      display: inline-block;
      width: 120px;
      color: #666;
    }
  }
}

.actions {
  display: flex;
  gap: 15px;
  justify-content: center;

  @media (max-width: 768px) {
    flex-direction: column;

    .el-button {
      width: 100%;
    }
  }
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>


