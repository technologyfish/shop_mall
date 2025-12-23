<template>
  <div class="user-center-home">
    <h2 class="welcome-title">Welcome, {{ user?.username }}!</h2>
    
    <div class="quick-links">
      <router-link to="/user-center/orders" class="quick-link-card">
        <el-icon class="card-icon"><ShoppingBag /></el-icon>
        <h3>My Orders</h3>
        <p>View and manage your orders</p>
      </router-link>

      <router-link to="/user-center/profile" class="quick-link-card">
        <el-icon class="card-icon"><User /></el-icon>
        <h3>Personal Info</h3>
        <p>Update your profile information</p>
      </router-link>

      <router-link to="/user-center/addresses" class="quick-link-card">
        <el-icon class="card-icon"><Location /></el-icon>
        <h3>Addresses</h3>
        <p>Manage delivery addresses</p>
      </router-link>

      <router-link to="/user-center/subscriptions" class="quick-link-card">
        <el-icon class="card-icon"><Promotion /></el-icon>
        <h3>My Subscriptions</h3>
        <p>View your active subscriptions</p>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { ShoppingBag, User, Location, Promotion } from '@element-plus/icons-vue'
import { useUserStore } from '@/store/user'

const userStore = useUserStore()
const user = computed(() => userStore.userInfo)
</script>

<style scoped lang="scss">
.user-center-home {
  .welcome-title {
    font-size: 28px;
    font-weight: 600;
    color: #333;
    margin-bottom: 30px;
  }

  .quick-links {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 20px;

    .quick-link-card {
      background: #fff;
      border: 1px solid #e8e8e8;
      border-radius: 12px;
      padding: 30px 20px;
      text-align: center;
      text-decoration: none;
      transition: all 0.3s;
      cursor: pointer;

      &:hover {
        border-color: var(--primary-color);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
      }

      .card-icon {
        font-size: 48px;
        color: var(--primary-color);
        margin-bottom: 15px;
      }

      h3 {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
      }

      p {
        font-size: 14px;
        color: #999;
        margin: 0;
      }
    }
  }

  @media (max-width: 768px) {
    .quick-links {
      grid-template-columns: repeat(2, 1fr);
      gap: 15px;

      .quick-link-card {
        padding: 20px 15px;

        .card-icon {
          font-size: 36px;
        }

        h3 {
          font-size: 16px;
        }

        p {
          font-size: 12px;
        }
      }
    }
  }
}
</style>




