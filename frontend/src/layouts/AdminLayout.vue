<template>
  <div class="admin-layout">
    <el-container>
      <!-- 侧边栏 -->
      <el-aside width="200px">
        <div class="logo">
          <h2>管理后台</h2>
        </div>
        <el-menu
          :default-active="$route.path"
          router
          background-color="#304156"
          text-color="#bfcbd9"
          active-text-color="#409eff"
        >
          <!-- 暂时隐藏数据统计 -->
          <!-- <el-menu-item index="/admin">
            <el-icon><data-line /></el-icon>
            <span>数据统计</span>
          </el-menu-item> -->
          <el-menu-item index="/admin/announcements">
            <el-icon><Bell /></el-icon>
            <span>公告管理</span>
          </el-menu-item>
          <el-menu-item index="/admin/banners">
            <el-icon><Picture /></el-icon>
            <span>Banner管理</span>
          </el-menu-item>
          <el-menu-item index="/admin/products">
            <el-icon><Goods /></el-icon>
            <span>商品管理</span>
          </el-menu-item>
          <el-sub-menu index="recipes">
            <template #title>
              <el-icon><Food /></el-icon>
              <span>食谱管理</span>
            </template>
            <el-menu-item index="/admin/recipe-categories">
              <span>分类管理</span>
            </el-menu-item>
            <el-menu-item index="/admin/recipes">
              <span>食谱列表</span>
            </el-menu-item>
          </el-sub-menu>
          <el-menu-item index="/admin/photos">
            <el-icon><Picture /></el-icon>
            <span>Photo管理</span>
          </el-menu-item>
          <el-sub-menu index="subscriptions">
            <template #title>
              <el-icon><Promotion /></el-icon>
              <span>订阅管理</span>
            </template>
            <el-menu-item index="/admin/subscription-plans">
              <span>订阅计划</span>
            </el-menu-item>
            <el-menu-item index="/admin/subscription-orders">
              <span>订阅订单</span>
            </el-menu-item>
          </el-sub-menu>
          <el-menu-item index="/admin/articles">
            <el-icon><Document /></el-icon>
            <span>文章管理</span>
          </el-menu-item>
          <el-menu-item index="/admin/journeys">
            <el-icon><Guide /></el-icon>
            <span>Journey管理</span>
          </el-menu-item>
          <el-menu-item index="/admin/orders">
            <el-icon><Tickets /></el-icon>
            <span>订单管理</span>
          </el-menu-item>
          <el-menu-item index="/admin/users">
            <el-icon><User /></el-icon>
            <span>用户管理</span>
          </el-menu-item>
          <el-menu-item index="/admin/contact-info">
            <el-icon><InfoFilled /></el-icon>
            <span>联系信息</span>
          </el-menu-item>
          <el-sub-menu index="forms">
            <template #title>
              <el-icon><ChatDotRound /></el-icon>
              <span>表单管理</span>
            </template>
            <el-menu-item index="/admin/contact-forms">
              <span>Contact收集</span>
            </el-menu-item>
            <el-menu-item index="/admin/mail-transfer-forms">
              <span>MailTransfer收集</span>
            </el-menu-item>
          </el-sub-menu>
          <el-menu-item index="/admin/promotions">
            <el-icon><Ticket /></el-icon>
            <span>促销活动</span>
          </el-menu-item>
          <el-menu-item index="/admin/shipping-settings">
            <el-icon><Van /></el-icon>
            <span>运费设置</span>
          </el-menu-item>
          <el-menu-item index="/admin/email-tasks">
            <el-icon><Message /></el-icon>
            <span>邮件任务</span>
          </el-menu-item>
        </el-menu>
      </el-aside>

      <el-container>
        <!-- 顶部栏 -->
        <el-header>
          <div class="header-content">
            <div></div>
            <el-dropdown>
              <span class="admin-name">
                {{ adminStore.adminInfo?.username }}
                <el-icon><arrow-down /></el-icon>
              </span>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item @click="handleLogout">退出登录</el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </div>
        </el-header>

        <!-- 主内容 -->
        <el-main>
          <router-view />
        </el-main>
      </el-container>
    </el-container>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Van } from '@element-plus/icons-vue'
import { useAdminStore } from '@/store/admin'

const router = useRouter()
const adminStore = useAdminStore()

onMounted(() => {
  if (adminStore.isLoggedIn) {
    adminStore.fetchAdminInfo().catch(() => {})
  }
})

const handleLogout = async () => {
  await adminStore.logout()
  ElMessage.success('退出登录成功')
  router.push('/admin/login')
}
</script>

<style scoped lang="scss">
.admin-layout {
  height: 100vh;

  .el-container {
    height: 100%;
  }

  .el-aside {
    background: #304156;
    
    .logo {
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #2b3a4a;

      h2 {
        color: #fff;
        font-size: 20px;
        margin: 0;
      }
    }

    .el-menu {
      border-right: none;
    }
  }

  .el-header {
    background: #fff;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;

    .header-content {
      width: 100%;
      display: flex;
      justify-content: space-between;
      align-items: center;

      .admin-name {
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
      }
    }
  }

  .el-main {
    background: #f0f2f5;
  }
}
</style>


