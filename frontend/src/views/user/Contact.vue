<template>
  <div class="contact-page">
    <!-- Top Section -->
    <div class="contact-header">
      <div class="container">
        <h1 class="main-title">Get in touch</h1>
        <p class="subtitle">We'd love to hear from you</p>
      </div>
    </div>

    <!-- Contact Info Cards -->
    <div class="contact-info-section">
      <div class="container">
        <div class="info-cards">
          <div class="info-card">
            <div class="icon-wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
              </svg>
            </div>
            <h3 class="card-title">Email</h3>
            <p class="card-content">{{ contactInfo.email?.value || 'Loading...' }}</p>
          </div>

          <div class="info-card">
            <div class="icon-wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
              </svg>
            </div>
            <h3 class="card-title">Phone</h3>
            <p class="card-content">{{ contactInfo.phone?.value || 'Loading...' }}</p>
          </div>

          <div class="info-card">
            <div class="icon-wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
              </svg>
            </div>
            <h3 class="card-title">Location</h3>
            <p class="card-content">{{ contactInfo.address?.value || 'Loading...' }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Contact Form Section -->
    <div class="form-section">
      <div class="container">
        <h2 class="form-title">Say Hello</h2>
        
        <form class="contact-form" @submit.prevent="handleSubmit">
          <div class="form-row">
            <div class="form-group">
              <input 
                v-model="form.name" 
                type="text" 
                placeholder="Name" 
                required 
              />
            </div>
            <div class="form-group">
              <input 
                v-model="form.email" 
                type="email" 
                placeholder="Email" 
                required 
              />
            </div>
          </div>

          <div class="form-group full-width">
            <input 
              v-model="form.phone" 
              type="tel" 
              placeholder="Phone" 
            />
          </div>

          <div class="form-group full-width">
            <textarea 
              v-model="form.comment" 
              placeholder="Comment" 
              rows="6" 
              required
            ></textarea>
          </div>

          <button type="submit" class="btn-submit" :disabled="loading">
            {{ loading ? 'Submitting...' : 'Submit' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import message from '@/utils/message'
import { getContactInfo, submitContactForm } from '@/api/contact'

const form = ref({
  name: '',
  email: '',
  phone: '',
  comment: ''
})

const contactInfo = ref({
  email: { value: '' },
  phone: { value: '' },
  address: { value: '' }
})

const loading = ref(false)

// 获取联系信息
const fetchContactInfo = async () => {
  try {
    const res = await getContactInfo()
    contactInfo.value = res.data.data
  } catch (error) {
    console.error('Failed to fetch contact info:', error)
  }
}

const handleSubmit = async () => {
  loading.value = true
  try {
    await submitContactForm(form.value)
    message.success('Thank you for your message! We will get back to you soon.')
    form.value = { name: '', email: '', phone: '', comment: '' }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to submit form. Please try again.')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchContactInfo()
})
</script>

<style lang="scss" scoped>
.contact-page {
  min-height: 100vh;
  background: var(--primary-lighter);
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

// Top Header Section
.contact-header {
  padding: 30px 0;
  text-align: center;
  
  .main-title {
    font-size: 48px;
    font-weight: bold;
    color: var(--secondary-color);
    margin-bottom: 16px;
    font-family: 'Courier New', serif;
  }
  
  .subtitle {
    font-size: 18px;
    color: var(--secondary-color);
  }
}

// Contact Info Cards Section
.contact-info-section {
  padding: 0 0 40px;


  .info-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
  }
  
  .info-card {
    background: var(--secondary-color);
    padding: 40px 30px;
    border-radius: 12px;
    text-align: center;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    
    &:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    }
    
    .icon-wrapper {
      width: 60px;
      height: 60px;
      margin: 0 auto 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      background: #FFF5EE;
      
      svg {
        width: 32px;
        height: 32px;
        color: var(--primary-color);
      }
    }
    
    .card-title {
      font-size: 20px;
      font-weight: bold;
      color: var(--primary-color);
      margin-bottom: 10px;
    }
    
    .card-content {
      font-size: 16px;
      color: #666;
      line-height: 1.6;
    }
  }
}

// Form Section
.form-section {
  background: var(--primary-lighter);
  padding: 0 100px 40px;
  
  .form-title {
    font-size: 42px;
    font-weight: bold;
    color: #fff;
    text-align: center;
    margin-bottom: 50px;
    font-family: 'Courier New', serif;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
  }
}

.contact-form {
  max-width: 800px;
  margin: 0 auto;
  
  .form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 20px;
  }
  
  .form-group {
    &.full-width {
      margin-bottom: 20px;
    }
    
    input,
    textarea {
      width: 100%;
      padding: 16px 20px;
      font-size: 16px;
      border: none;
      border-radius: 30px;
      background: #F5F5F5;
      transition: all 0.3s;
      font-family: inherit;
      
      &::placeholder {
        color: #999;
      }
      
      &:focus {
        outline: none;
        background: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      }
    }
    
    textarea {
      border-radius: 20px;
      resize: vertical;
      min-height: 150px;
    }
  }
  
  .btn-submit {
    border: unset;
    display: block;
    width: 100%;
    max-width: 300px;
    margin: 30px auto 0;
    padding: 16px 40px;
    background: var(--secondary-color);
    color: var(--text-color);
    border: none;
    border-radius: 30px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    

    
    
    &:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
  }
}

@media (max-width: 768px) {
  @import "@/assets/scss/module/m_contact.scss";

  .container{
    max-width: 100%;
  }
  .contact-header {
    .main-title {
      font-size: 36px;
    }
  }
  
  .form-section {
    padding: 60px 0;
    
    .form-title {
      font-size: 32px;
    }
  }
  
  .contact-form {
    .form-row {
      grid-template-columns: 1fr;
    }
  }
}
</style>


