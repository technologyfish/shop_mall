<template>
  <div class="mobile-layout">
    <!-- 移动端顶部导航 -->
    <header class="m-header">
      <div class="m-header-content">
        <h1>商城</h1>
        <div class="m-menu-icon" @click="showMenu = !showMenu">
          <el-icon><menu /></el-icon>
        </div>
      </div>
    </header>

    <!-- 侧边菜单 -->
    <el-drawer v-model="showMenu" direction="rtl" size="70%">
      <template #header>
        <h3>菜单</h3>
      </template>
      <el-menu>
        <el-menu-item @click="goto('/m_')">首页</el-menu-item>
        <el-menu-item @click="goto('/m_products')">商品</el-menu-item>
        <el-menu-item v-if="userStore.isLoggedIn" @click="goto('/m_cart')">购物车</el-menu-item>
        <el-menu-item v-if="userStore.isLoggedIn" @click="goto('/m_orders')">我的订单</el-menu-item>
        <el-menu-item v-if="userStore.isLoggedIn" @click="goto('/m_profile')">个人中心</el-menu-item>
        <el-menu-item v-if="!userStore.isLoggedIn" @click="goto('/m_login')">登录</el-menu-item>
        <el-menu-item v-if="userStore.isLoggedIn" @click="handleLogout">退出</el-menu-item>
      </el-menu>
    </el-drawer>

    <!-- 主内容 -->
    <main class="m-main">
      <router-view />
    </main>

    <!-- 底部导航 -->
    <footer class="m-footer" v-if="userStore.isLoggedIn">
      <div class="m-nav-item" @click="goto('/m_')">
        <el-icon><home-filled /></el-icon>
        <span>首页</span>
      </div>
      <div class="m-nav-item" @click="goto('/m_products')">
        <el-icon><goods /></el-icon>
        <span>商品</span>
      </div>
      <div class="m-nav-item" @click="goto('/m_cart')">
        <el-icon><shopping-cart /></el-icon>
        <span>购物车</span>
      </div>
      <div class="m-nav-item" @click="goto('/m_profile')">
        <el-icon><user /></el-icon>
        <span>我的</span>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { useUserStore } from '@/store/user'

const router = useRouter()
const userStore = useUserStore()
const showMenu = ref(false)

onMounted(() => {
  if (userStore.isLoggedIn) {
    userStore.fetchUserInfo().catch(() => {})
  }
})

const goto = (path) => {
  showMenu.value = false
  router.push(path)
}

const handleLogout = async () => {
  await userStore.logout()
  ElMessage.success('退出登录成功')
  router.push('/m_')
}
</script>

<style lang="scss">
@import "@/styles/user-theme.scss";
</style>

<style scoped lang="scss">
.mobile-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f5f7fa;
}

.m-header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  z-index: 100;

  .m-header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px;
    
    h1 {
      font-size: 18px;
      margin: 0;
      color: #409eff;
    }

    .m-menu-icon {
      font-size: 24px;
      cursor: pointer;
    }
  }
}

.m-main {
  flex: 1;
  margin-top: 50px;
  margin-bottom: 60px;
  padding: 10px;
}

.m-footer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #fff;
  border-top: 1px solid #eee;
  display: flex;
  justify-content: space-around;
  padding: 8px 0;
  z-index: 100;

  .m-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    color: #666;

    &:active {
      color: #409eff;
    }

    .el-icon {
      font-size: 24px;
    }

    span {
      font-size: 12px;
    }
  }
}
</style>







