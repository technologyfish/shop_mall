<template>
  <div class="login-page">
    <div class="login-container">
      <div class="login-card">


        <form class="login-form" @submit.prevent="handleLogin">
          <div class="form-group">
            <label>Username or Email</label>
            <input
              v-model="form.username"
              type="text"
              placeholder="Enter your username or email"
              required
            />
          </div>

          <div class="form-group">
            <label>Password</label>
            <input
              v-model="form.password"
              type="password"
              placeholder="Enter your password"
              required
            />
          </div>

          <div class="forgot-password">
            <label class="remember-me">
              <input type="checkbox" v-model="form.remember" />
              <span>Remember me</span>
            </label>
            <router-link to="/forgot-password" class="forgot-link">
              Forgot password?
            </router-link>
          </div>

          <button type="submit" class="btn-submit" :disabled="loading">
            {{ loading ? 'Logging in...' : 'Log In' }}
          </button>

          <div class="register-link">
            Don't have an account?
            <router-link to="/register">Sign up</router-link>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/store/user'
import { ElMessageBox } from 'element-plus'
import message from '@/utils/message'

const router = useRouter()
const userStore = useUserStore()

const form = ref({
  username: '',
  password: '',
  remember: false
})

const loading = ref(false)

const handleLogin = async () => {
  if (loading.value) return
  
  loading.value = true
  try {
    await userStore.login(form.value)
    // message.success('Login successful!')
    router.push('/')
  } catch (error) {
    const response = error.response
    message.error(error.response?.data?.message || error.message || 'Login failed')

  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss">
@import "@/assets/scss/module/login.scss";

@media (max-width: 768px) {
  @import "@/assets/scss/module/m_login.scss";
}
</style>
