<template>
  <div class="success-page">
    <div class="container">
      <!-- Loading State -->
      <div class="success-card" v-if="loading">
        <div class="loading-icon">
          <el-icon :size="60" class="is-loading"><Loading /></el-icon>
        </div>
        <h1>Processing...</h1>
        <p class="message">Please wait while we verify your subscription.</p>
      </div>

      <!-- Error State -->
      <div class="success-card error-card" v-else-if="error">
        <div class="error-icon">
          <el-icon :size="100" color="#F56C6C"><CircleClose /></el-icon>
        </div>
        <h1>Something went wrong</h1>
        <p class="message">{{ error }}</p>
        <div class="actions">
          <el-button type="primary" size="large" @click="retryVerify">
            Retry
          </el-button>
          <el-button size="large" @click="goToHome">
            Back to Home
          </el-button>
        </div>
      </div>

      <!-- Success State -->
      <div class="success-card" v-else>
        <div class="success-icon">
          <el-icon :size="100" color="#4CAF50"><CircleCheck /></el-icon>
        </div>
        <h1>Subscription Successful!</h1>
        <p class="message">
          Thank you for joining The Sauce Club! Your subscription has been activated.
        </p>
        <div class="details" v-if="subscription">
          <h3>Subscription Details</h3>
          <p><strong>Plan:</strong> {{ subscription.plan_name }}</p>
          <p><strong>Price:</strong> ${{ subscription.price }}/month</p>
          <p><strong>Status:</strong> {{ subscription.status }}</p>
        </div>
        <div class="actions">
          <el-button type="primary" size="large" @click="goToSubscriptions">
            View My Subscriptions
          </el-button>
          <el-button size="large" @click="goToHome">
            Back to Home
          </el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { CircleCheck, CircleClose, Loading } from '@element-plus/icons-vue'
import { verifySubscription } from '@/api/subscription'

const router = useRouter()
const route = useRoute()
const subscription = ref(null)
const loading = ref(true)
const error = ref(null)

onMounted(() => {
  verifyPayment()
})

const verifyPayment = async () => {
  const sessionId = route.query.session_id
  
  if (!sessionId) {
    error.value = 'No session ID found'
    loading.value = false
    return
  }

  try {
    loading.value = true
    error.value = null
    
    const res = await verifySubscription(sessionId)
    subscription.value = res.data.data
    
  } catch (err) {
    console.error('Verification failed:', err)
    error.value = err.response?.data?.message || 'Failed to verify subscription'
  } finally {
    loading.value = false
  }
}

const retryVerify = () => {
  verifyPayment()
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const goToSubscriptions = () => {
  router.push('/user-center/subscriptions')
}

const goToHome = () => {
  router.push('/')
}
</script>

<style scoped lang="scss">
.success-page {
  min-height: 80vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  background: #f5f5f5;

  .container {
    max-width: 600px;
    width: 100%;
  }

  .success-card {
    background: white;
    border-radius: 16px;
    padding: 60px 40px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);

    .loading-icon,
    .success-icon,
    .error-icon {
      margin-bottom: 30px;
    }

    .loading-icon .el-icon {
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    h1 {
      font-size: 32px;
      font-weight: bold;
      margin-bottom: 20px;
      color: #333;
    }

    .message {
      font-size: 16px;
      color: #666;
      margin-bottom: 30px;
      line-height: 1.6;
    }

    .details {
      background: #f9f9f9;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 30px;
      text-align: left;

      h3 {
        font-size: 18px;
        margin-bottom: 15px;
        color: #333;
      }

      p {
        font-size: 14px;
        color: #666;
        margin: 8px 0;

        strong {
          color: #333;
        }
      }
    }

    .actions {
      display: flex;
      gap: 15px;
      justify-content: center;
      flex-wrap: wrap;
    }
  }

  .error-card {
    h1 {
      color: #F56C6C;
    }
  }
}
</style>




