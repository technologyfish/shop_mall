<template>
  <div class="profile-content-page">
    <!-- 移动端头像上传区域 -->
    <div class="mobile-avatar-section">
      <div class="avatar-upload" @click="handleAvatarClick">
        <img :src="profile.avatar || defaultAvatar" alt="Avatar" class="avatar-image" />
        <div class="avatar-overlay">
          <el-icon><Camera /></el-icon>
          <span>Change Avatar</span>
        </div>
      </div>
      <input 
        type="file" 
        ref="avatarInputMobile" 
        accept="image/*" 
        style="display: none"
        @change="handleAvatarChange"
      />
    </div>

    <form @submit.prevent="handleUpdateProfile">
      <div class="form-grid">
        <div class="form-group">
          <label>Username</label>
          <input
              v-model="profile.username"
              type="text"
              disabled
              class="disabled"
          />
        </div>

        <div class="form-group">
          <label>Nickname</label>
          <input
              v-model="profile.nickname"
              type="text"
              placeholder="Your display name"
          />
        </div>

        <div class="form-group">
          <label>Email</label>
          <input
              v-model="profile.email"
              type="email"
              disabled
              class="disabled"
          />
        </div>

        <div class="form-group">
          <label>Phone</label>
          <input
              v-model="profile.phone"
              type="tel"
              placeholder="Your phone number"
          />
        </div>
      </div>

      <button type="submit" class="btn-submit" :disabled="loading">
        {{ loading ? 'Saving...' : 'Save Changes' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { ArrowLeft, Camera } from '@element-plus/icons-vue'
import { useUserStore } from '@/store/user'
import message from '@/utils/message'

const userStore = useUserStore()
const defaultAvatar = 'https://cube.elemecdn.com/0/88/03b0d39583f48206768a7534e55bcpng.png'

const profile = ref({
  username: '',
  nickname: '',
  email: '',
  phone: '',
  avatar: ''
})

const loading = ref(false)
const avatarInputMobile = ref(null)

onMounted(() => {
  // Load user profile
  if (userStore.userInfo) {
    profile.value = { ...userStore.userInfo }
  }
})

// 监听用户信息变化
watch(() => userStore.userInfo, (newUser) => {
  if (newUser) {
    profile.value = { ...newUser }
  }
}, { immediate: true })

// 更新个人资料
const handleUpdateProfile = async () => {
  loading.value = true
  try {
    await userStore.updateProfile(profile.value)
    message.success('Profile updated successfully')
  } catch (error) {
    message.error(error.message || 'Failed to update profile')
  } finally {
    loading.value = false
  }
}

// 移动端头像点击
const handleAvatarClick = () => {
  avatarInputMobile.value?.click()
}

// 移动端头像上传
const handleAvatarChange = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append('file', file)

  try {
    await userStore.updateAvatar(formData)
    message.success('Avatar updated successfully')
    // 更新本地预览
    if (userStore.userInfo) {
      profile.value.avatar = userStore.userInfo.avatar
    }
  } catch (error) {
    console.error(error)
    message.error('Failed to upload avatar')
  }
}
</script>

<style scoped lang="scss">
.profile-content-page {
  // 移动端头像上传区域默认隐藏
  .mobile-avatar-section {
    display: none;
  }

  .page-title {
    font-size: 24px;
    font-weight: bold;
    margin: 0 0 30px 0;
    color: #333;
  }

  form {
    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 30px 40px;
      margin-bottom: 40px;

      @media (max-width: 768px) {
        grid-template-columns: 1fr;
        gap: 24px;
      }
    }

    .form-group {
      display: flex;
      flex-direction: column;

      label {
        font-size: 14px;
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
        display: block;
      }

      input {
        width: 100%;
        padding: 12px 16px;
        font-size: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        transition: all 0.3s;
        background: #fff;
        color: #333;

        &:focus {
          outline: none;
          border-color: #4a90e2;
          box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        &.disabled {
          background: #f5f5f5;
          color: #999;
          cursor: not-allowed;

          &:focus {
            border-color: #ddd;
            box-shadow: none;
          }
        }

        &::placeholder {
          color: #999;
        }
      }
    }

    .btn-submit {
      width: 200px;
      padding: 14px 28px;
      font-size: 16px;
      font-weight: 600;
      color: white;
      background: var(--primary-light);
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s;

      &:hover:not(:disabled) {
        background: var(--primary-light);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(74, 144, 226, 0.3);
      }

      &:active:not(:disabled) {
        transform: translateY(0);
      }

      &:disabled {
        background: #ccc;
        cursor: not-allowed;
        opacity: 0.6;
      }

      @media (max-width: 768px) {
        width: 100%;
      }
    }
  }

  // 移动端样式
  @media (max-width: 768px) {
    padding: 20px;

    // 显示移动端头像上传区域
    .mobile-avatar-section {
      display: flex;
      justify-content: center;
      margin-bottom: 25px;

      .avatar-upload {
        position: relative;
        width: 100px;
        height: 100px;
        cursor: pointer;

        .avatar-image {
          width: 100%;
          height: 100%;
          border-radius: 50%;
          object-fit: cover;
          border: 3px solid var(--primary-color);
        }

        .avatar-overlay {
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          border-radius: 50%;
          background: rgba(0, 0, 0, 0.6);
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          opacity: 0;
          transition: opacity 0.3s;
          color: white;
          gap: 5px;

          .el-icon {
            font-size: 24px;
          }

          span {
            font-size: 11px;
          }
        }

        &:active .avatar-overlay {
          opacity: 1;
        }
      }
    }
    
    form {
      max-width: 100%;
    }

    .form-grid {
      grid-template-columns: 1fr;
      gap: 15px;
    }

    .form-group {
      label {
        font-size: 14px;
      }

      input {
        font-size: 14px;
        padding: 10px;
      }
    }

    .btn-submit {
      font-size: 16px;
      padding: 12px;
    }
  }
}
</style>
