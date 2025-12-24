<template>
  <div class="navbar-wrapper">
    <!-- 顶部滚动公告 -->
    <div class="announcement-bar" v-if="announcements.length">
      <div class="announcement-scroll">
        <div class="announcement-track">
          <span v-for="item in announcements" :key="item.id" class="announcement-item">
            <a :href="item.link || '#'" class="announcement-text">
              {{ item.content }}
            </a>
          </span>
          <!-- 复制一遍内容实现无缝循环 -->
          <span v-for="item in announcements" :key="'copy-' + item.id" class="announcement-item">
            <a :href="item.link || '#'" class="announcement-text">
              {{ item.content }}
            </a>
          </span>
        </div>
      </div>
    </div>

    <!-- 导航栏 -->
    <nav class="main-nav">
      <div class="nav-container">
        <!-- Logo -->
<!--        <router-link to="/" class="logo">-->
<!--          <img src="@/assets/images/logo.png" alt="The Chilli Trail" />-->
<!--        </router-link>-->

        <!-- 移动端遮罩 -->
        <div class="nav-overlay" :class="{ active: mobileMenuOpen }" @click="closeMobileMenu"></div>
        
        <!-- 菜单 -->
        <div class="nav-menu" :class="{ active: mobileMenuOpen }">
          <!-- 移动端关闭按钮 -->
          <button class="mobile-close-btn" @click="closeMobileMenu">
            <span class="close-icon">✕</span>
          </button>
          
          <router-link to="/home" class="nav-link" exact @click="closeMobileMenu">Home</router-link>
          <router-link to="/shop" class="nav-link" @click="closeMobileMenu">Shop</router-link>
          <router-link to="/our-journey" class="nav-link" @click="closeMobileMenu">Our Journey</router-link>
          <router-link to="/recipes" class="nav-link" @click="closeMobileMenu">Recipes</router-link>
          <router-link to="/photos" class="nav-link" @click="closeMobileMenu">Photos</router-link>
          <router-link to="/subscription" class="nav-link" @click="closeMobileMenu">Join the Club</router-link>
          <router-link to="/contact" class="nav-link" @click="closeMobileMenu">Contact</router-link>
        </div>

        <!-- 右侧按钮 -->
        <div class="nav-actions">
          <!-- 移动端菜单按钮 -->
          <button class="mobile-menu-btn" @click="toggleMobileMenu">
            <span></span>
            <span></span>
            <span></span>
          </button>

          <div class="user-menu" v-if="isLoggedIn">
            <a @click="handleAvatarClick" class="icon-btn" style="cursor: pointer;">
              <img :src="user?.avatar ? getImageUrl(user.avatar) :  defaultAvatar" class="avatar" />
            </a>
          </div>


          <router-link to="/login" class="btn-primary" v-else>
            <img :src="defaultAvatar" class="avatar"/>
          </router-link>

          <div class="cart-menu">
            <router-link to="/cart" class="icon-btn cart-btn">
              <img :src="iconCat" alt="Cart" class="cart-icon" />
              <span class="cart-count">({{ cartCount }})</span>
            </router-link>
          </div>
        </div>
      </div>
    </nav>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/store/user'
import { useCartStore } from '@/store/cart'
import { getAnnouncements } from '@/api/home'
import iconCat from '@/assets/images/icon-cat.png'
import defaultAvatar from '@/assets/images/avatar.png'
import {getImageUrl} from "@/utils/image";

const router = useRouter()
const userStore = useUserStore()
const cartStore = useCartStore()

const announcements = ref([])
const mobileMenuOpen = ref(false)
const userMenuOpen = ref(false)
const cartOpen = ref(false)

const isLoggedIn = computed(() => userStore.isLoggedIn)
const user = computed(() => userStore.user)
const cartCount = computed(() => cartStore.cartCount)
const cartItems = computed(() => cartStore.cartItems)
const cartTotal = computed(() => cartStore.cartTotal)

const fetchAnnouncements = async () => {
  try {
    const res = await getAnnouncements()
    announcements.value = res.data.data || []
  } catch (error) {
    console.error('获取公告失败:', error)
  }
}

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
  // 控制body滚动
  if (mobileMenuOpen.value) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
}

const closeMobileMenu = () => {
  mobileMenuOpen.value = false
  document.body.style.overflow = ''
}

// 头像点击：PC端跳orders，移动端跳user-center
const handleAvatarClick = () => {
  if (window.innerWidth <= 768) {
    router.push('/user-center')
  } else {
    router.push('/user-center/orders')
  }
}


const handleLogout = async () => {
  await userStore.logout()
  router.push('/login')
}

onMounted(async () => {
  fetchAnnouncements()
  if (isLoggedIn.value) {
    await cartStore.fetchCart()
  }
})
</script>

<style lang="scss">
  .user-menu, .cart-menu {
    position: relative;
    height: 100%;
    display: flex;
    align-items: center;
  }

  .dropdown, .cart-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-radius: 8px;
    padding: 10px 0;
    min-width: 150px;
    z-index: 1000;
    
    /* Fix hover gap */
    &::before {
      content: '';
      position: absolute;
      top: -10px;
      left: 0;
      width: 100%;
      height: 10px;
      background: transparent;
    }
  }
  
  .user-dropdown {
    width: auto;
    min-width: 180px;
    padding: 15px;
  }

  .user-dropdown-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    
    .nickname {
      font-weight: bold;
      font-size: 14px;
      color: #333;
      max-width: 120px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    
    .logout-link {
      color: #f56c6c;
      font-size: 13px;
      text-decoration: none;
      white-space: nowrap;
      cursor: pointer;
      
      &:hover {
        text-decoration: underline;
      }
    }
  }
  
  .cart-dropdown {
    width: 300px;
    padding: 0;
  }

  .cart-count {
    font-size: 14px;
    font-weight: bold;
    margin-left: 5px;
    color: var(--text-white);
  }

  .cart-items-list {
    max-height: 300px;
    overflow-y: auto;
    padding: 10px;
  }
  
  .cart-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    
    &:last-child {
      border-bottom: none;
    }
    
    .item-thumb {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 4px;
    }
    
    .item-info {
      flex: 1;
      .item-name {
        font-size: 14px;
        margin-bottom: 4px;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }
      .item-meta {
        font-size: 12px;
        color: #999;
      }
    }
    
    .item-price {
      font-weight: bold;
      color: var(--primary-color);
    }
  }
  
  .cart-total {
    padding: 15px;
    background: #f9f9f9;
    display: flex;
    justify-content: space-between;
    font-weight: bold;
    border-top: 1px solid #eee;
  }
  
  .btn-checkout-mini {
    display: block;
    margin: 10px 15px 15px;
    background: var(--primary-color);
    color: #fff;
    text-align: center;
    padding: 10px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: bold;
    
    &:hover {
      background: var(--primary-dark);
    }
  }
  
  .empty-cart {
    padding: 30px;
    text-align: center;
    color: #999;
  }

@import "@/assets/scss/components/navbar.scss";

@media (max-width: 768px) {
  @import "@/assets/scss/components/m_navbar.scss";
}
</style>

