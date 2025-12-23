<template>
  <div class="sign-out-page">
    <div class="page-header">

    </div>

    <div class="sign-out-content">
      <div class="sign-out-card">
        <el-icon class="warning-icon"><WarningFilled /></el-icon>
        <h2>Are you sure you want to sign out?</h2>
        <p class="description">You will need to sign in again to access your account.</p>

        <div class="button-group">
          <el-button @click="handleCancel" size="large">Cancel</el-button>
          <el-button type="danger" @click="handleLogout" size="large" :loading="loading">
            Sign out
          </el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import message from '@/utils/message'
import { WarningFilled } from '@element-plus/icons-vue'
import { useUserStore } from '@/store/user'

const router = useRouter()
const userStore = useUserStore()
const loading = ref(false)

const handleCancel = () => {
  router.push('/user-center')
}

const handleLogout = async () => {
  loading.value = true
  try {
    await userStore.logout()
    // message.success('Successfully signed out')
    router.push('/login')
  } catch (error) {
    message.error('Failed to sign out')
    console.error('Logout error:', error)
  } finally {
    loading.value = false
  }
}

// 检测是否移动端并自动退出
const checkAndAutoLogout = () => {
  if (window.innerWidth <= 768) {
    // 移动端直接退出
    handleLogout()
  }
}

onMounted(() => {
  checkAndAutoLogout()
})
</script>

<style scoped lang="scss">
.sign-out-page {
  .page-header {
    margin-bottom: 30px;
  }

  .page-title {
    font-size: 24px;
    font-weight: bold;
    margin: 0;
  }

  .sign-out-content {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
  }

  .sign-out-card {
    background: white;
    border-radius: 12px;
    padding: 50px 60px;
    text-align: center;
    max-width: 500px;
    width: 100%;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);

    .warning-icon {
      font-size: 64px;
      color: #e6a23c;
      margin-bottom: 20px;
    }

    h2 {
      font-size: 22px;
      font-weight: 600;
      color: #333;
      margin-bottom: 15px;
    }

    .description {
      font-size: 14px;
      color: #666;
      margin-bottom: 40px;
      line-height: 1.6;
    }

    .button-group {
      display: flex;
      gap: 15px;
      justify-content: center;

      .el-button {
        min-width: 120px;
      }
    }
  }

  // 移动端样式
  @media (max-width: 768px) {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 2000;
    padding: 0;

    .page-header {
      display: none;
    }

    .sign-out-content {
      min-height: 100vh;
      padding: 20px;
    }

    .sign-out-card {
      max-width: 400px;
      padding: 40px 30px;
      margin: 0 auto;

      .warning-icon {
        font-size: 56px;
        margin-bottom: 16px;
      }

      h2 {
        font-size: 20px;
        margin-bottom: 12px;
      }

      .description {
        font-size: 14px;
        margin-bottom: 30px;
      }

      .button-group {
        flex-direction: column;
        gap: 12px;

        .el-button {
          width: 100%;
          min-width: unset;
        }
      }
    }
  }
}
</style>




