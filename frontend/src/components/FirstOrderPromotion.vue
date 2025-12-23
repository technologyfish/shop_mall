<template>
  <!-- 悬浮按钮 -->
  <div v-if="showFloatButton" class="float-button-container">
    <button class="float-button" @click="openFormModal">
      GET {{ discountValue }}% OFF!
    </button>
    <button class="close-btn" @click="closeFloatButton">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
      </svg>
    </button>
  </div>

  <!-- 表单弹窗 -->
  <div v-if="showFormModal" class="modal-overlay" @click.self="closeFormModal">
    <div class="modal-content form-modal">
      <button class="modal-close" @click="closeFormModal">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
          <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
        </svg>
      </button>
      
      <h2 class="modal-title">GET SAUCY AND SAVE<br/>{{ discountValue }}%</h2>
      
      <div class="bottles-icon">
        <svg viewBox="0 0 120 60" xmlns="http://www.w3.org/2000/svg" class="bottles-svg">
          <!-- 左手拿瓶子 -->
          <g transform="translate(10, 5)">
            <path d="M15 50 Q10 45 12 35 L14 15 Q14 10 18 8 L22 8 Q26 10 26 15 L28 35 Q30 45 25 50 Z" fill="none" stroke="#000" stroke-width="2"/>
            <rect x="16" y="5" width="8" height="5" fill="none" stroke="#000" stroke-width="2"/>
          </g>
          <!-- 中间瓶子 -->
          <g transform="translate(45, 0)">
            <path d="M12 55 Q8 50 10 38 L12 18 Q12 12 16 10 L20 10 Q24 12 24 18 L26 38 Q28 50 24 55 Z" fill="none" stroke="#000" stroke-width="2"/>
            <rect x="14" y="5" width="8" height="6" fill="none" stroke="#000" stroke-width="2"/>
          </g>
          <!-- 右手拿瓶子 -->
          <g transform="translate(80, 5)">
            <path d="M15 50 Q10 45 12 35 L14 15 Q14 10 18 8 L22 8 Q26 10 26 15 L28 35 Q30 45 25 50 Z" fill="none" stroke="#000" stroke-width="2"/>
            <rect x="16" y="5" width="8" height="5" fill="none" stroke="#000" stroke-width="2"/>
          </g>
        </svg>
      </div>
      
      <p class="modal-desc">Sign up for saucy emails and get<br/>{{ discountValue }}% off your order!</p>
      
      <form @submit.prevent="handleSubmit" class="promo-form">
        <input 
          v-model="formData.nickname" 
          type="text" 
          placeholder="First Name" 
          class="promo-input"
          required
        />
        <input 
          v-model="formData.email" 
          type="email" 
          placeholder="Email" 
          class="promo-input"
          required
        />
        <button type="submit" class="btn-submit" :disabled="submitting">
          {{ submitting ? 'SUBMITTING...' : 'SIGN ME UP!' }}
        </button>
      </form>
      
      <button class="btn-no-thanks" @click="closeFormModal">NO THANKS</button>
      
      <p class="note">Discount applies to one-off orders only. Not valid on subscriptions.</p>
    </div>
  </div>

  <!-- 成功弹窗 -->
  <div v-if="showSuccessModal" class="modal-overlay" @click.self="closeSuccessModal">
    <div class="modal-content success-modal">
      <button class="modal-close" @click="closeSuccessModal">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
          <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
        </svg>
      </button>
      
      <h2 class="success-title">YOU'RE IN!</h2>
      
      <div class="success-icon">
        <svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" class="person-svg">
          <!-- 简单人物图标 -->
          <circle cx="40" cy="20" r="12" fill="none" stroke="#000" stroke-width="2"/>
          <path d="M20 75 Q25 50 40 45 Q55 50 60 75" fill="none" stroke="#000" stroke-width="2"/>
          <path d="M30 30 Q35 35 40 30" fill="none" stroke="#000" stroke-width="2"/>
        </svg>
      </div>
      
      <p class="success-desc">Your 10% off code is:</p>
      
      <div class="code-box">
        <span class="off-code">{{ offCode }}</span>
        <button class="copy-btn" @click="copyCode" :title="copied ? 'Copied!' : 'Copy'">
          <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
            <path v-if="!copied" d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
            <path v-else d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
          </svg>
        </button>
      </div>
      
      <router-link to="/register" class="btn-register" @click="closeSuccessModal">
        REGISTER NOW
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useUserStore } from '@/store/user'
import axios from 'axios'

const userStore = useUserStore()

// 状态
const showFloatButton = ref(false)
const showFormModal = ref(false)
const showSuccessModal = ref(false)
const submitting = ref(false)
const offCode = ref('')
const copied = ref(false)
const discountValue = ref(10) // 默认10%，从接口获取

const formData = ref({
  nickname: '',
  email: ''
})

// 计算属性
const isLoggedIn = computed(() => userStore.isLoggedIn)

onMounted(async () => {
  // 检查本次会话是否已关闭
  const closedThisSession = sessionStorage.getItem('first_order_promo_closed')
  
  if (closedThisSession) {
    return
  }
  
  try {
    // 先获取促销活动折扣值
    const promoRes = await axios.get('/api/off-code/promotion')
    if (promoRes.data.data?.active) {
      discountValue.value = promoRes.data.data.discount_value || 10
    }
    
    // 调用后端检查是否应该显示弹窗
    const res = await axios.get('/api/off-code/check-popup')
    
    if (res.data.data?.show) {
      // 如果已登录，预填用户信息
      if (isLoggedIn.value && userStore.userInfo) {
        formData.value.nickname = userStore.userInfo.nickname || userStore.userInfo.username || ''
        formData.value.email = userStore.userInfo.email || ''
      }
      
      // 延迟显示悬浮按钮
      setTimeout(() => {
        showFloatButton.value = true
      }, 2000)
    }
  } catch (error) {
    console.error('Failed to check popup status:', error)
    // 如果接口失败，对未登录用户默认显示
    if (!isLoggedIn.value) {
      setTimeout(() => {
        showFloatButton.value = true
      }, 2000)
    }
  }
})

// 方法
const closeFloatButton = () => {
  showFloatButton.value = false
  sessionStorage.setItem('first_order_promo_closed', 'true')
}

const openFormModal = () => {
  showFormModal.value = true
}

const closeFormModal = () => {
  showFormModal.value = false
}

const closeSuccessModal = () => {
  showSuccessModal.value = false
  showFloatButton.value = false
  sessionStorage.setItem('first_order_promo_closed', 'true')
}

const handleSubmit = async () => {
  if (!formData.value.nickname || !formData.value.email) {
    return
  }

  submitting.value = true
  try {
    const res = await axios.post('/api/off-code/collect', {
      nickname: formData.value.nickname,
      email: formData.value.email
    })

    offCode.value = res.data.data.off_code
    showFormModal.value = false
    showSuccessModal.value = true
    showFloatButton.value = false
    
  } catch (error) {
    console.error('Failed to submit:', error)
    alert(error.response?.data?.message || 'Failed to submit. Please try again.')
  } finally {
    submitting.value = false
  }
}

const copyCode = async () => {
  try {
    await navigator.clipboard.writeText(offCode.value)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (err) {
    // Fallback
    const textArea = document.createElement('textarea')
    textArea.value = offCode.value
    document.body.appendChild(textArea)
    textArea.select()
    document.execCommand('copy')
    document.body.removeChild(textArea)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  }
}
</script>

<style scoped lang="scss">
// 悬浮按钮
.float-button-container {
  position: fixed;
  bottom: 30px;
  right: 30px;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  
  .float-button {
    background: var(--primary-color, #E85A2A);
    color: white;
    border: none;
    padding: 18px 30px;
    border-radius: 30px;
    font-size: 18px;
    font-weight: 900;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
    font-family: 'Courier Prime', 'Courier New', monospace;
    
    &:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 25px rgba(0, 0, 0, 0.4);
    }
  }
  
  .close-btn {
    position: absolute;
    top: -10px;
    right: -10px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #000;
    color: white;
    border: 2px solid white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 0;
    
    &:hover {
      background: #333;
    }
  }
}

// 弹窗遮罩
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 20px;
}

// 弹窗内容
.modal-content {
  background: #F5E6D3;
  border-radius: 16px;
  padding: 40px;
  max-width: 450px;
  width: 100%;
  position: relative;
  text-align: center;
  
  .modal-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
    color: #666;
    
    &:hover {
      color: #000;
    }
  }
}

// 表单弹窗
.form-modal {
  .modal-title {
    color: #D2691E;
    font-size: 32px;
    font-weight: 900;
    margin: 0 0 20px 0;
    line-height: 1.2;
    font-family: 'Courier Prime', 'Courier New', monospace;
  }
  
  .bottles-icon {
    margin: 20px 0;
    
    .bottles-svg {
      width: 120px;
      height: 60px;
    }
  }
  
  .modal-desc {
    font-size: 16px;
    color: #000;
    margin-bottom: 25px;
    line-height: 1.5;
  }
  
  .promo-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
  }
  
  .promo-input {
    width: 100%;
    height: 50px;
    padding: 0 20px;
    border: 2px solid #000;
    border-radius: 25px;
    font-size: 16px;
    outline: none;
    background: white;
    box-sizing: border-box;
    
    &:focus {
      border-color: var(--primary-color, #E85A2A);
    }
    
    &::placeholder {
      color: #999;
      font-style: italic;
    }
  }
  
  .btn-submit {
    width: 100%;
    height: 55px;
    background: var(--primary-color, #E85A2A);
    color: white;
    border: none;
    border-radius: 30px;
    font-size: 18px;
    font-weight: 900;
    cursor: pointer;
    transition: all 0.3s;
    font-family: 'Courier Prime', 'Courier New', monospace;
    
    &:hover:not(:disabled) {
      background: #c94a1a;
      transform: translateY(-2px);
    }
    
    &:disabled {
      opacity: 0.7;
      cursor: not-allowed;
    }
  }
  
  .btn-no-thanks {
    background: none;
    border: none;
    color: #000;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 10px;
    text-decoration: none;
    letter-spacing: 1px;
    
    &:hover {
      text-decoration: underline;
    }
  }
  
  .note {
    font-size: 12px;
    color: #666;
    margin-top: 20px;
    font-style: italic;
    line-height: 1.4;
  }
}

// 成功弹窗
.success-modal {
  .success-title {
    color: #D2691E;
    font-size: 36px;
    font-weight: 900;
    margin: 0 0 20px 0;
    font-family: 'Courier Prime', 'Courier New', monospace;
  }
  
  .success-icon {
    margin: 20px 0;
    
    .person-svg {
      width: 80px;
      height: 80px;
    }
  }
  
  .success-desc {
    font-size: 16px;
    color: #000;
    margin-bottom: 20px;
  }
  
  .code-box {
    background: var(--primary-color, #E85A2A);
    color: white;
    padding: 15px 25px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    margin-bottom: 25px;
    
    .off-code {
      font-size: 24px;
      font-weight: 900;
      letter-spacing: 2px;
      font-family: 'Courier Prime', 'Courier New', monospace;
    }
    
    .copy-btn {
      background: none;
      border: 2px solid white;
      border-radius: 6px;
      padding: 8px;
      cursor: pointer;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      
      &:hover {
        background: rgba(255, 255, 255, 0.2);
      }
    }
  }
  
  .btn-register {
    display: inline-block;
    background: #000;
    color: white;
    padding: 15px 40px;
    border-radius: 30px;
    font-size: 16px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.3s;
    
    &:hover {
      background: #333;
      transform: translateY(-2px);
    }
  }
}

// 移动端适配
@media (max-width: 768px) {
  .float-button-container {
    bottom: 20px;
    right: 20px;
    
    .float-button {
      padding: 14px 24px;
      font-size: 16px;
    }
  }
  
  .modal-content {
    padding: 30px 25px;
    margin: 10px;
  }
  
  .form-modal {
    .modal-title {
      font-size: 26px;
    }
  }
  
  .success-modal {
    .success-title {
      font-size: 28px;
    }
    
    .code-box .off-code {
      font-size: 20px;
    }
  }
}
</style>



