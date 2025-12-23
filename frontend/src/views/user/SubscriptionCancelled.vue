<template>
  <div class="cancelled-page">
    <div class="container">
      <div class="cancelled-card">
        <div class="cancelled-icon">
          <el-icon :size="100" color="#FF5722"><Close /></el-icon>
        </div>
        <h1>Subscription Cancelled</h1>
        <p class="message">
          Your subscription process was cancelled. No charges have been made.
        </p>
        <div class="actions">
          <el-button type="primary" size="large" @click="goToSubscription">
            Try Again
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
import { useRouter } from 'vue-router'
import { Close } from '@element-plus/icons-vue'

const router = useRouter()

const goToSubscription = () => {
  router.push('/subscription')
}

const goToHome = () => {
  router.push('/')
}
</script>

<style scoped lang="scss">
.cancelled-page {
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

  .cancelled-card {
    background: white;
    border-radius: 16px;
    padding: 60px 40px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);

    .cancelled-icon {
      margin-bottom: 30px;
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

    .actions {
      display: flex;
      gap: 15px;
      justify-content: center;
      flex-wrap: wrap;
    }
  }
}
</style>




