<template>
  <div class="dashboard-page">
    <h1 class="page-title">数据统计</h1>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background: #409eff">
          <el-icon><document /></el-icon>
        </div>
        <div class="stat-info">
          <p class="stat-label">总订单数</p>
          <p class="stat-value">{{ statistics.total_orders || 0 }}</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: #67c23a">
          <el-icon><money /></el-icon>
        </div>
        <div class="stat-info">
          <p class="stat-label">总销售额</p>
          <p class="stat-value">¥{{ statistics.total_amount || 0 }}</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: #e6a23c">
          <el-icon><calendar /></el-icon>
        </div>
        <div class="stat-info">
          <p class="stat-label">今日订单</p>
          <p class="stat-value">{{ statistics.today_orders || 0 }}</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: #f56c6c">
          <el-icon><coin /></el-icon>
        </div>
        <div class="stat-info">
          <p class="stat-label">今日销售额</p>
          <p class="stat-value">¥{{ statistics.today_amount || 0 }}</p>
        </div>
      </div>
    </div>

    <div class="order-stats">
      <div class="card">
        <h3>订单状态统计</h3>
        <div class="order-stats-list">
          <div class="order-stat-item">
            <span>待支付</span>
            <el-tag>{{ statistics.pending_orders || 0 }}</el-tag>
          </div>
          <div class="order-stat-item">
            <span>已支付</span>
            <el-tag type="success">{{ statistics.paid_orders || 0 }}</el-tag>
          </div>
          <div class="order-stat-item">
            <span>已发货</span>
            <el-tag type="primary">{{ statistics.shipped_orders || 0 }}</el-tag>
          </div>
          <div class="order-stat-item">
            <span>已完成</span>
            <el-tag type="info">{{ statistics.completed_orders || 0 }}</el-tag>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getStatistics } from '@/api/admin'

const statistics = ref({})

onMounted(async () => {
  const res = await getStatistics()
  statistics.value = res.data
})
</script>

<style scoped lang="scss">
.dashboard-page {
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;

    .stat-card {
      display: flex;
      align-items: center;
      gap: 20px;
      padding: 25px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);

      .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 28px;
      }

      .stat-info {
        flex: 1;

        .stat-label {
          color: #666;
          font-size: 14px;
          margin-bottom: 5px;
        }

        .stat-value {
          font-size: 24px;
          font-weight: bold;
          color: #333;
        }
      }
    }
  }

  .order-stats {
    .order-stats-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 20px;
      margin-top: 20px;

      .order-stat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: #f5f7fa;
        border-radius: 4px;

        span {
          font-size: 16px;
        }
      }
    }
  }
}
</style>







