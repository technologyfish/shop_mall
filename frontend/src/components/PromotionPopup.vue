<template>
  <el-dialog
    v-model="visible"
    :show-close="true"
    width="500px"
    :close-on-click-modal="false"
    class="promotion-popup"
    @close="handleClose"
  >
    <template #header>
      <div class="popup-header">
        <h2>GET SAUCY AND SAVE</h2>
        <p class="discount-badge">{{ promotion?.discount_value }}%</p>
      </div>
    </template>

    <div class="popup-content">
      <div class="bottles-icon">
        <svg viewBox="0 0 200 100" xmlns="http://www.w3.org/2000/svg">
          <text x="100" y="50" text-anchor="middle" font-size="60" font-family="Arial" fill="#000">🌶️🌶️🌶️</text>
        </svg>
      </div>

      <p class="description">
        Sign up for saucy emails and get<br />{{ promotion?.discount_value }}% off your order!
      </p>

      <el-form :model="formData" @submit.prevent="handleSubmit">
        <el-form-item>
          <input
            v-model="formData.nickname"
            placeholder="First Name"
            class="custom-input"
          />
        </el-form-item>
        <el-form-item>
          <input
            v-model="formData.email"
            type="email"
            placeholder="Email"
            class="custom-input"
          />
        </el-form-item>
        <el-form-item>
          <button
            type="button"
            class="btn-signup"
            @click="handleSubmit"
            :disabled="submitting"
          >
            {{ submitting ? 'SUBMITTING...' : 'SIGN ME UP!' }}
          </button>
        </el-form-item>
        <el-form-item>
          <button
            type="button"
            class="btn-no-thanks"
            @click="handleNoThanks"
          >
            NO THANKS
          </button>
        </el-form-item>
      </el-form>

      <p class="note">
        Discount applies to one-off orders only. Not valid on subscriptions.
      </p>
    </div>
  </el-dialog>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { useUserStore } from '@/store/user'
import axios from 'axios'

const userStore = useUserStore()
const visible = ref(false)
const promotion = ref(null)
const submitting = ref(false)

const formData = ref({
  nickname: '',
  email: ''
})

onMounted(async () => {
  // 检查用户是否已提交过表单或点击过NO THANKS
  const hasSubmitted = localStorage.getItem('promotion_submitted')
  const hasDeclined = localStorage.getItem('promotion_declined')
  
  if (hasSubmitted || hasDeclined) {
    return
  }

  // 等待用户信息加载完成（如果已登录）
  if (userStore.isLoggedIn) {
    // 等待最多2秒加载用户信息
    let attempts = 0
    while (!userStore.userInfo && attempts < 20) {
      await new Promise(resolve => setTimeout(resolve, 100))
      attempts++
    }
    
    // 自动回填邮箱
    if (userStore.userInfo) {
      formData.value.email = userStore.userInfo.email || ''
      formData.value.nickname = userStore.userInfo.nickname || userStore.userInfo.username || ''
    }
  }

  // 获取活跃的促销活动
  try {
    const res = await axios.get('http://localhost:8000/api/promotions/active')
    if (res.data.data) {
      promotion.value = res.data.data
      // 延迟1秒显示弹窗
      setTimeout(() => {
        visible.value = true
      }, 1000)
    }
  } catch (error) {
    console.error('Failed to fetch promotion:', error)
  }
})

const handleSubmit = async () => {
  if (!formData.value.nickname || !formData.value.email) {
    ElMessage.warning('Please fill in all fields')
    return
  }

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.value.email)) {
    ElMessage.warning('Please enter a valid email address')
    return
  }

  submitting.value = true
  try {
    await axios.post('http://localhost:8000/api/mail-transfer/submit', {
      nickname: formData.value.nickname,
      email: formData.value.email,
      promotion_id: promotion.value?.id
    })

    ElMessage.success('Thank you! Check your email for your discount code.')
    localStorage.setItem('promotion_submitted', 'true')
    localStorage.setItem('promotion_id', promotion.value?.id)
    visible.value = false
  } catch (error) {
    ElMessage.error(error.response?.data?.message || 'Failed to submit. Please try again.')
  } finally {
    submitting.value = false
  }
}

const handleNoThanks = () => {
  localStorage.setItem('promotion_declined', 'true')
  visible.value = false
}

const handleClose = () => {
  // 用户点击关闭按钮也视为拒绝
  if (!localStorage.getItem('promotion_submitted')) {
    localStorage.setItem('promotion_declined', 'true')
  }
}
</script>

<style scoped lang="scss">
.promotion-popup {
  :deep(.el-dialog__header) {
    padding: 0;
    margin: 0;
  }

  :deep(.el-dialog__body) {
    padding: 0;
    background: #f5e6d3;
  }

  :deep(.el-dialog) {
    border-radius: 12px;
    overflow: hidden;
  }

  .popup-header {
    background: #f5e6d3;
    color: #d2691e;
    padding: 30px 30px 10px;
    text-align: center;

    h2 {
      font-size: 32px;
      margin: 0;
      font-weight: 900;
      letter-spacing: 1px;
      line-height: 1.2;
    }

    .discount-badge {
      font-size: 72px;
      font-weight: 900;
      margin: 10px 0 0 0;
      color: #d2691e;
    }
  }

  .popup-content {
    padding: 0 40px 40px;
    background: #f5e6d3;

    .bottles-icon {
      text-align: center;
      margin: 20px 0;
      font-size: 48px;
    }

    .description {
      text-align: center;
      font-size: 18px;
      margin-bottom: 30px;
      color: #000;
      line-height: 1.5;
      font-weight: 500;
    }

    .note {
      text-align: center;
      font-size: 11px;
      color: #666;
      margin-top: 15px;
      font-style: italic;
      line-height: 1.4;
    }

    .el-form {
      .el-form-item {
        margin-bottom: 15px;
      }
    }

    .custom-input {
      width: 100%;
      height: 50px;
      padding: 0 15px;
      font-size: 16px;
      border: 2px solid #000;
      border-radius: 25px;
      outline: none;
      transition: all 0.3s;

      &:focus {
        border-color: #d2691e;
      }

      &::placeholder {
        color: #999;
      }
    }

    .btn-signup {
      width: 100%;
      height: 55px;
      background: #d2691e;
      color: white;
      border: none;
      border-radius: 30px;
      font-size: 18px;
      font-weight: bold;
      letter-spacing: 1px;
      cursor: pointer;
      transition: all 0.3s;

      &:hover:not(:disabled) {
        background: #a0522d;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(210, 105, 30, 0.4);
      }

      &:disabled {
        opacity: 0.7;
        cursor: not-allowed;
      }
    }

    .btn-no-thanks {
      width: 100%;
      height: 45px;
      background: transparent;
      color: #4a90e2;
      border: none;
      border-radius: 25px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      text-decoration: underline;

      &:hover {
        color: #2c5aa0;
      }
    }
  }
}
</style>
