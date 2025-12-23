<template>
  <div class="register-page">
    <div class="register-container">
      <div class="register-card">
        <div class="logo-section">
<!--          <img src="@/assets/images/logo.png" alt="The Chilli Trail" class="logo" />-->
          <h1 class="title">Create Account</h1>
<!--          <p class="subtitle">Join The Chilli Trail family</p>-->
        </div>

        <form class="register-form" @submit.prevent="handleRegister">
          <div class="form-group">
            <label>Username *</label>
            <input
              v-model="form.username"
              type="text"
              placeholder="Choose a username"
              required
            />
          </div>

          <div class="form-group">
            <label>Nickname</label>
            <input
              v-model="form.nickname"
              type="text"
              placeholder="Your display name (optional)"
            />
          </div>

          <div class="form-group">
            <label>Email *</label>
            <input
              v-model="form.email"
              type="email"
              placeholder="Enter your email"
              required
            />
          </div>

          <div class="form-group">
            <label>Phone</label>
            <input
              v-model="form.phone"
              type="tel"
              placeholder="Your phone number (optional)"
            />
          </div>

          <div class="form-group">
            <label>Password *</label>
            <input
              v-model="form.password"
              type="password"
              placeholder="Create a password (min. 6 characters)"
              required
              minlength="6"
            />
          </div>

          <div class="form-group">
            <label>Confirm Password *</label>
            <input
              v-model="form.password_confirmation"
              type="password"
              placeholder="Confirm your password"
              required
            />
          </div>

          <div class="form-group">
            <label>Off Code (Optional)</label>
            <input
              v-model="form.off_code"
              type="text"
              placeholder="Enter your off code for 10% discount"
            />
            <p class="field-hint" v-if="form.off_code">
              Using this code will activate your first order discount!
            </p>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="form.agree" required />
              <span>
                I agree to the 
                <router-link to="/terms">Terms & Conditions</router-link> 
                and 
                <router-link to="/privacy">Privacy Policy</router-link>
              </span>
            </label>
          </div>

          <button type="submit" class="btn-submit" :disabled="loading">
            {{ loading ? 'Creating Account...' : 'Create Account' }}
          </button>

          <div class="login-link">
            Already have an account?
            <router-link to="/login">Log in</router-link>
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
import message from '@/utils/message'

const router = useRouter()
const userStore = useUserStore()

const form = ref({
  username: '',
  nickname: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  off_code: '',
  agree: false
})

const loading = ref(false)

const handleRegister = async () => {
  if (loading.value) return
  
  if (form.value.password !== form.value.password_confirmation) {
    message.error('Passwords do not match')
    return
  }
  
  if (!form.value.agree) {
    message.error('Please agree to the Terms & Conditions')
    return
  }
  
  loading.value = true
  try {
    await userStore.register(form.value)
    // message.success('Registration successful! Please login.')
    router.push('/login')
  } catch (error) {
    message.error(error.message || 'Registration failed')
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss">
@import "@/assets/scss/module/register.scss";

@media (max-width: 768px) {
  @import "@/assets/scss/module/m_register.scss";
}
</style>
