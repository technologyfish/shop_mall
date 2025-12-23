<template>
  <div class="user-center-layout">
    <!-- Loading 弹窗 -->
    <PageLoading :visible="loading" text="Loading..." />

    <div class="container">
      <div class="layout-wrapper">
        <!-- 左侧边栏 - PC端始终显示，移动端根据路由显示 -->
        <aside class="sidebar" :class="{ 'mobile-hidden': !shouldShowSidebarOnMobile }">
          <div class="user-info">
            <div class="avatar-section">
              <div class="avatar-wrapper" @click="handleAvatarClick">
                <div class="avatar">
                  <img :src="user?.avatar || defaultAvatar" :alt="user?.username" />
                </div>
                <div class="avatar-overlay">
                  <el-icon><Camera /></el-icon>
                  <span>Change Avatar</span>
                </div>
                <input
                  type="file"
                  ref="avatarInput"
                  accept="image/*"
                  style="display: none"
                  @change="handleAvatarChange"
                />
              </div>
              <!-- 移动端编辑按钮 -->
              <button class="mobile-edit-btn" @click="goToProfile">
                <el-icon><Edit /></el-icon>
              </button>
            </div>
            <h3 class="username">{{ user?.username || 'User' }}</h3>
            <p class="join-date">Joined {{ formatDate(user?.created_at) }}</p>
          </div>

          <nav class="nav-menu">
            <router-link to="/user-center/orders" class="nav-item">
              <el-icon><ShoppingBag /></el-icon>
              <span>My Orders</span>
              <el-badge v-if="pendingOrdersCount" :value="pendingOrdersCount" class="badge" />
            </router-link>
            <router-link to="/user-center/profile" class="nav-item">
              <el-icon><User /></el-icon>
              <span>Personal Info</span>
            </router-link>
            <router-link to="/user-center/addresses" class="nav-item">
              <el-icon><Location /></el-icon>
              <span>Addresses</span>
            </router-link>
            <router-link to="/user-center/subscriptions" class="nav-item">
              <el-icon><Promotion /></el-icon>
              <span>My Subscriptions</span>
            </router-link>
            <div  @click="handleLogout"  class="nav-item">
              <el-icon><SwitchButton /></el-icon>
              <span>Sign out</span>
            </div>
          </nav>
        </aside>

        <!-- 右侧内容区域 - PC端始终显示，移动端根据路由显示 -->
        <main class="main-content" :class="{ 'mobile-hidden': !shouldShowContentOnMobile }">
          <router-view />
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ShoppingBag, User, Location, SwitchButton, Camera, Promotion, Edit } from '@element-plus/icons-vue'
import { useUserStore } from '@/store/user'
import PageLoading from '@/components/PageLoading.vue'
import message from '@/utils/message'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const user = computed(() => userStore.userInfo)
const pendingOrdersCount = ref(0)
const avatarInput = ref(null)
const loading = ref(true)

const defaultAvatar = 'https://cube.elemecdn.com/0/88/03b0d39583f48206768a7534e55bcpng.png'

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

// 判断当前是否在个人中心根路径
const isUserCenterRoot = computed(() => {
  return route.path === '/user-center' || route.path === '/user-center/'
})

// 移动端：只在根路径显示侧边栏
const shouldShowSidebarOnMobile = computed(() => {
  return isUserCenterRoot.value
})

// 移动端：只在非根路径显示内容
const shouldShowContentOnMobile = computed(() => {
  return !isUserCenterRoot.value
})

onMounted(async () => {
  loading.value = true
  
  try {
    // 确保用户信息已加载
    if (!userStore.userInfo) {
      await userStore.fetchUserInfo()
    }
  } finally {
    loading.value = false
  }
})

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit' })
}

const handleAvatarClick = () => {
  avatarInput.value.click()
}

const handleAvatarChange = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append('file', file)

  try {
    await userStore.updateAvatar(formData)
    // message.success('Avatar updated successfully')
  } catch (error) {
    console.error(error)
    message.error('Failed to upload avatar')
  }
}

const goToProfile = () => {
  router.push('/user-center/profile')
}
</script>

<style lang="scss">
@import "@/styles/user-theme.scss";
</style>

<style scoped lang="scss">
.user-center-layout {
  min-height: calc(100vh - 180px);
  padding: 40px 0;
  background: var(--text-white);

  .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
  }

  @media (max-width: 768px) {
    .container {
      max-width: 100%;
    }
  }

  .layout-wrapper {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 30px;
  }

  .sidebar {
    background: var(--text-white);
    border-radius: 12px;
    padding: 30px 0;
    height: fit-content;
    position: sticky;
    top: 100px;

    .user-info {
      text-align: center;
      padding: 0 20px 25px;
      border-bottom: 1px solid #f0f0f0;

      .avatar-section {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        margin-bottom: 15px;

        .mobile-edit-btn {
          display: none;
        }
      }

      .avatar-wrapper {
        position: relative;
        width: 100px;
        height: 100px;
        cursor: pointer;

        .avatar {
          width: 100%;
          height: 100%;
          border-radius: 50%;
          border: 3px dashed #4a90e2;
          padding: 5px;

          img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
          }
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
            font-size: 12px;
          }
        }

        &:hover .avatar-overlay {
          opacity: 1;
        }
      }

      .username {
        font-size: 20px;
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
      }

      .join-date {
        font-size: 13px;
        color: #999;
      }
    }

    .nav-menu {
      padding: 15px 0;

      .nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px 25px;
        color: #666;
        text-decoration: none;
        transition: all 0.3s;
        cursor: pointer;
        position: relative;
        border-bottom: 1px solid #f0f0f0; // 添加分割线

        &:last-child {
          border-bottom: none; // 最后一项不显示分割线
        }

        .el-icon {
          font-size: 20px;
        }

        span {
          flex: 1;
          font-size: 15px;
        }

        .badge {
          margin-left: auto;
        }

        &:hover {
          background: var(--primary-lighter);
          color: white;
        }

        &.router-link-active {
          background: var(--primary-lighter);
          color: white;
          font-weight: 500;
        }
      }
    }

    @media (max-width: 768px) {
      position: relative;
      top: 0;

      .user-info {
        .avatar-section {
          .mobile-edit-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.3s;

            .el-icon {
              font-size: 20px;
            }

            &:active {
              transform: scale(0.95);
              background: var(--primary-dark);
            }
          }
        }
      }

      .nav-menu {
        .nav-item {
          padding: 18px 25px;
          
          // 移动端不显示选中状态
          &.router-link-active {
            background: transparent;
            color: #666;
            font-weight: normal;
          }

          &:hover {
            background: #f8f8f8;
          }

          &:active {
            background: #e8e8e8;
          }

          // 移动端隐藏 Personal Info 菜单项
          &[href="/user-center/profile"] {
            display: none;
          }
        }
      }
    }
  }

  .main-content {
    background: white;
    border-radius: 12px;
    padding: 30px;
    min-height: 500px;
  }

  // 移动端特殊样式
  @media (max-width: 768px) {
    padding: 0;
    
    .container {
      padding: 0;
    }

    .layout-wrapper {
      grid-template-columns: 1fr;
      gap: 0;
    }

    .sidebar {
      border-radius: 0;
      min-height: calc(100vh - 180px);

      &.mobile-hidden {
        display: none;
      }
    }

    .main-content {
      padding: 20px;
      border-radius: 0;

      &.mobile-hidden {
        display: none;
      }
    }
  }
}
</style>

