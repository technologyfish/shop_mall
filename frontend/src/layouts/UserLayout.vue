<template>
  <div class="user-layout">
    <!-- 导航栏 -->
    <Navbar />

    <!-- 主内容区 -->
    <main class="main-content">
      <router-view />
    </main>

    <!-- 页脚 -->
    <Footer />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useUserStore } from '@/store/user'
import { useCartStore } from '@/store/cart'
import Navbar from '@/components/Navbar.vue'
import Footer from '@/components/Footer.vue'

const userStore = useUserStore()
const cartStore = useCartStore()

onMounted(() => {
  // 如果用户已登录，获取用户信息和购物车
  if (userStore.token) {
    userStore.fetchUserInfo().catch(() => {})
    cartStore.fetchCart().catch(() => {})
  }
})
</script>

<style lang="scss">
@import "@/styles/user-theme.scss";
</style>

<style lang="scss" scoped>
.user-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.main-content {
  flex: 1;
}
</style>
