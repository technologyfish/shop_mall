<template>
  <div class="forgot-password-page">
    <div class="container">
      <div class="forgot-password-card">
        <div class="logo-section">
          <h1>Reset Password</h1>
          <p class="subtitle">Enter your email to receive a verification code</p>
        </div>

        <!-- Step 1: 输入邮箱 -->
        <div v-if="step === 1" class="form-section">
          <el-form :model="form" :rules="rules" ref="formRef" @submit.prevent="handleSendCode">
            <el-form-item prop="email">
              <el-input
                v-model="form.email"
                type="email"
                size="large"
                placeholder="Email Address"
                prefix-icon="Message"
                :disabled="loading"
              />
            </el-form-item>

            <el-button
              type="primary"
              size="large"
              class="submit-btn"
              :loading="loading"
              @click="handleSendCode"
              native-type="submit"
            >
              {{ loading ? 'Sending...' : 'Send Verification Code' }}
            </el-button>
          </el-form>

          <div class="links">
            <router-link to="/login" class="link">Back to Login</router-link>
          </div>
        </div>

        <!-- Step 2: 输入验证码和新密码 -->
        <div v-else class="form-section">
          <div class="code-sent-info">
            <el-icon class="success-icon" color="#67c23a" :size="48"><CircleCheck /></el-icon>
            <p>Verification code sent to</p>
            <p class="email">{{ form.email }}</p>
          </div>

          <el-form :model="form" :rules="resetRules" ref="resetFormRef" @submit.prevent="handleResetPassword">
            <el-form-item prop="code">
              <el-input
                v-model="form.code"
                size="large"
                placeholder="6-digit Verification Code"
                prefix-icon="ChatDotSquare"
                maxlength="6"
                :disabled="loading"
              />
            </el-form-item>

            <el-form-item prop="password">
              <el-input
                v-model="form.password"
                type="password"
                size="large"
                placeholder="New Password (min 6 characters)"
                prefix-icon="Lock"
                show-password
                :disabled="loading"
              />
            </el-form-item>

            <el-form-item prop="confirmPassword">
              <el-input
                v-model="form.confirmPassword"
                type="password"
                size="large"
                placeholder="Confirm New Password"
                prefix-icon="Lock"
                show-password
                :disabled="loading"
              />
            </el-form-item>

            <el-button
              type="primary"
              size="large"
              class="submit-btn"
              :loading="loading"
              @click="handleResetPassword"
              native-type="submit"
            >
              {{ loading ? 'Resetting...' : 'Reset Password' }}
            </el-button>
          </el-form>

          <div class="links">
            <el-button link @click="handleResendCode" :disabled="countdown > 0">
              {{ countdown > 0 ? `Resend in ${countdown}s` : 'Resend Code' }}
            </el-button>
            <span class="divider">|</span>
            <el-button link @click="handleBackToStep1">Change Email</el-button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { CircleCheck } from '@element-plus/icons-vue'
import request from '@/utils/request'

const router = useRouter()
const formRef = ref(null)
const resetFormRef = ref(null)
const loading = ref(false)
const step = ref(1)
const countdown = ref(0)
let countdownTimer = null

const form = reactive({
  email: '',
  code: '',
  password: '',
  confirmPassword: ''
})

const rules = {
  email: [
    { required: true, message: 'Please enter your email', trigger: 'blur' },
    { type: 'email', message: 'Please enter a valid email', trigger: 'blur' }
  ]
}

const validateConfirmPassword = (rule, value, callback) => {
  if (value === '') {
    callback(new Error('Please confirm your password'))
  } else if (value !== form.password) {
    callback(new Error('Passwords do not match'))
  } else {
    callback()
  }
}

const resetRules = {
  code: [
    { required: true, message: 'Please enter verification code', trigger: 'blur' },
    { len: 6, message: 'Code must be 6 digits', trigger: 'blur' }
  ],
  password: [
    { required: true, message: 'Please enter new password', trigger: 'blur' },
    { min: 6, message: 'Password must be at least 6 characters', trigger: 'blur' }
  ],
  confirmPassword: [
    { required: true, validator: validateConfirmPassword, trigger: 'blur' }
  ]
}

// 发送验证码
const handleSendCode = async () => {
  try {
    await formRef.value.validate()
    loading.value = true

    const res = await request({
      url: '/api/auth/forgot-password/send-code',
      method: 'post',
      data: { email: form.email }
    })

    ElMessage.success(res.data.message || 'Verification code sent successfully')
    step.value = 2
    startCountdown(60) // 60秒倒计时

  } catch (error) {
    if (error.response?.data?.message) {
      ElMessage.error(error.response.data.message)
    } else {
      ElMessage.error(error.message || 'Failed to send code')
    }
  } finally {
    loading.value = false
  }
}

// 重置密码
const handleResetPassword = async () => {
  try {
    await resetFormRef.value.validate()
    loading.value = true

    const res = await request({
      url: '/api/auth/forgot-password/reset',
      method: 'post',
      data: {
        email: form.email,
        code: form.code,
        password: form.password
      }
    })

    ElMessage.success(res.data.message || 'Password reset successfully')
    
    // 延迟跳转到登录页
    setTimeout(() => {
      router.push('/login')
    }, 1500)

  } catch (error) {
    if (error.response?.data?.message) {
      ElMessage.error(error.response.data.message)
    }
  } finally {
    loading.value = false
  }
}

// 重新发送验证码
const handleResendCode = async () => {
  if (countdown.value > 0) return
  
  loading.value = true
  try {
    await request({
      url: '/api/auth/forgot-password/send-code',
      method: 'post',
      data: { email: form.email }
    })

    ElMessage.success('Verification code resent')
    startCountdown(60)
  } catch (error) {
    ElMessage.error(error.response?.data?.message || 'Failed to resend code')
  } finally {
    loading.value = false
  }
}

// 返回第一步
const handleBackToStep1 = () => {
  step.value = 1
  form.code = ''
  form.password = ''
  form.confirmPassword = ''
  stopCountdown()
}

// 开始倒计时
const startCountdown = (seconds) => {
  countdown.value = seconds
  countdownTimer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      stopCountdown()
    }
  }, 1000)
}

// 停止倒计时
const stopCountdown = () => {
  if (countdownTimer) {
    clearInterval(countdownTimer)
    countdownTimer = null
  }
  countdown.value = 0
}

// 组件卸载时清理定时器
onUnmounted(() => {
  stopCountdown()
})
</script>

<style scoped lang="scss">
.forgot-password-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--primary-lighter);
  padding: 20px;

  .container {
    width: 100%;
    max-width: 540px;
  }

  .forgot-password-card {
    background: white;
    border-radius: 16px;
    padding: 50px 40px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);

    @media (max-width: 768px) {
      padding: 40px 30px;
    }
  }

  .logo-section {
    text-align: center;
    margin-bottom: 40px;

    h1 {
      font-size: 32px;
      font-weight: 700;
      color: #333;
      margin: 0 0 12px 0;
    }

    .subtitle {
      font-size: 15px;
      color: #666;
      margin: 0;
    }
  }

  .form-section {
    .el-form-item {
      margin-bottom: 24px;
    }

    .submit-btn {
      background: var(--primary-lighter);
      border: unset;
      width: 100%;
      height: 48px;
      font-size: 16px;
      font-weight: 600;
      margin-top: 10px;
    }

    .code-sent-info {
      text-align: center;
      margin-bottom: 30px;
      padding: 20px;
      background: #f0f9ff;
      border-radius: 12px;

      .success-icon {
        margin-bottom: 12px;
      }

      p {
        margin: 4px 0;
        font-size: 14px;
        color: #666;

        &.email {
          font-size: 16px;
          font-weight: 600;
          color: #333;
        }
      }
    }

    .links {
      margin-top: 24px;
      text-align: center;
      font-size: 14px;

      .link {
        color: var(--primary-lighter);
        text-decoration: none;
        transition: color 0.3s;

        &:hover {
          color: var(--primary-lighter);
          text-decoration: underline;
        }
      }

      .divider {
        margin: 0 12px;
        color: #ddd;
      }

      .el-button {
        color: #667eea;

        &:hover {
          color: #764ba2;
        }

        &:disabled {
          color: #ccc;
          cursor: not-allowed;
        }
      }
    }
  }
}

:deep(.el-input__wrapper) {
  border-radius: 8px;
  box-shadow: 0 0 0 1px #dcdfe6 inset;
  transition: all 0.3s;

  &:hover {
    box-shadow: 0 0 0 1px #c0c4cc inset;
  }
}

:deep(.el-input__wrapper.is-focus) {
  box-shadow: 0 0 0 1px #667eea inset !important;
}
</style>
